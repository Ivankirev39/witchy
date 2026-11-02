<?php

class ContactRateLimiter
{
    private PDO $db;

    private const MAX_SUBMISSIONS = 5;
    private const TIME_WINDOW_MINUTES = 15;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function submitContactRequest(
        ?int $userId,
        string $name,
        string $email,
        string $category,
        string $subject,
        string $message,
        string $ipAddress
    ): bool {
        $ipHash = hash_hmac(
            "sha256",
            $ipAddress,
            CONTACT_RATE_LIMIT_SECRET
        );

        $this->db->beginTransaction();

        try {
            // Create a lock row for this IP if one does not exist.
            $keyStmt = $this->db->prepare("
                INSERT IGNORE INTO contact_rate_limit_keys (ip_hash)
                VALUES (?)
            ");

            $keyStmt->execute([$ipHash]);

            // Lock this IP's row to prevent simultaneous submissions.
            $lockStmt = $this->db->prepare("
                SELECT ip_hash
                FROM contact_rate_limit_keys
                WHERE ip_hash = ?
                FOR UPDATE
            ");

            $lockStmt->execute([$ipHash]);

            // Count submissions from this IP in the last 15 minutes.
            $countStmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM contact_rate_limit_events
                WHERE ip_hash = ?
                AND submitted_at >= NOW() - INTERVAL 15 MINUTE
            ");

            $countStmt->execute([$ipHash]);

            $submissionCount = (int) $countStmt->fetchColumn();

            if ($submissionCount >= self::MAX_SUBMISSIONS) {
                $this->db->rollBack();
                return false;
            }

            // Save the support request.
            $requestStmt = $this->db->prepare("
                INSERT INTO support_request
                (user_id, name, email, category, subject, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $requestStmt->execute([
                $userId,
                $name,
                $email,
                $category,
                $subject,
                $message
            ]);

            // Record the successful submission.
            $eventStmt = $this->db->prepare("
                INSERT INTO contact_rate_limit_events (ip_hash)
                VALUES (?)
            ");

            $eventStmt->execute([$ipHash]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}