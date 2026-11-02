<?php

class RegistrationRateLimiter
{
    private PDO $db;
    private int $maxAttempts = 10;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function allowAttempt(string $ip): bool
    {
        $ipKey = hash("sha256", $ip);

        // Atomically count attempts within a 60-minute window.
        $stmt = $this->db->prepare("
            INSERT INTO registration_rate_limit
                (ip_key, window_started_at, attempts)
            VALUES (?, UTC_TIMESTAMP(), 1)
            ON DUPLICATE KEY UPDATE
                attempts = IF(
                    window_started_at <= DATE_SUB(
                        UTC_TIMESTAMP(), INTERVAL 60 MINUTE
                    ),
                    1,
                    LEAST(attempts + 1, 11)
                ),
                window_started_at = IF(
                    window_started_at <= DATE_SUB(
                        UTC_TIMESTAMP(), INTERVAL 60 MINUTE
                    ),
                    UTC_TIMESTAMP(),
                    window_started_at
                )
        ");

        $stmt->execute([$ipKey]);

        $stmt = $this->db->prepare("
            SELECT attempts
            FROM registration_rate_limit
            WHERE ip_key = ?
        ");

        $stmt->execute([$ipKey]);

        return (int) $stmt->fetchColumn() <= $this->maxAttempts;
    }
}
