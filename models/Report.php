<?php

class Report
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Check whether a user has already submitted a pending report
    public function hasAlreadyReported(
        int $reporterId,
        ?int $reportedUserId = null,
        ?int $commentId = null,
        ?int $postId = null
    ): bool {
        $targets = array_filter(
            [$reportedUserId, $commentId, $postId],
            static fn(?int $id): bool => $id !== null
        );

        if (count($targets) !== 1) {
            throw new InvalidArgumentException("Exactly one report target is required.");
        }

        if ($reportedUserId !== null) {
            $sql = "SELECT report_id FROM reports
                WHERE reporter_id = ? AND reported_user_id = ? AND status = 'pending'
                LIMIT 1";
            $targetId = $reportedUserId;
        } elseif ($commentId !== null) {
            $sql = "SELECT report_id FROM reports
                WHERE reporter_id = ? AND comment_id = ? AND status = 'pending'
                LIMIT 1";
            $targetId = $commentId;
        } else {
            $sql = "SELECT report_id FROM reports
                WHERE reporter_id = ? AND post_id = ? AND status = 'pending'
                LIMIT 1";
            $targetId = $postId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reporterId, $targetId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // Create a report against a user
    public function createUserReport(
        int $reporterId,
        int $reportedUserId,
        string $reason,
        ?string $details = null
    ): ?int {
        if ($reporterId === $reportedUserId) {
            throw new InvalidArgumentException("You cannot report yourself.");
        }

        if ($reason === "" || strlen($reason) > 100) {
            throw new InvalidArgumentException("Invalid report reason.");
        }

        $details = trim($details ?? "");
        $details = $details === "" ? null : $details;

        if ($this->hasAlreadyReported($reporterId, $reportedUserId)) {
            return null;
        }

        $sql = "
            INSERT INTO reports
            (reporter_id, reported_user_id, reason, details)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $reporterId,
            $reportedUserId,
            $reason,
            $details
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Create a report against a comment
    public function createCommentReport(
        int $reporterId,
        int $commentId,
        string $reason,
        ?string $details = null
    ): ?int {
        if ($reason === "" || strlen($reason) > 100) {
            throw new InvalidArgumentException("Invalid report reason.");
        }

        $details = trim($details ?? "");
        $details = $details === "" ? null : $details;

        // Find the author of the comment
        $sql = "
            SELECT user_id
            FROM comment
            WHERE comment_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$commentId]);

        $commentOwner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commentOwner) {
            throw new InvalidArgumentException("Comment does not exist.");
        }

        if ((int) $commentOwner["user_id"] === $reporterId) {
            throw new InvalidArgumentException("You cannot report your own comment.");
        }

        if ($this->hasAlreadyReported($reporterId, null, $commentId)) {
            return null;
        }

        $sql = "
            INSERT INTO reports
            (reporter_id, comment_id, reason, details)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $reporterId,
            $commentId,
            $reason,
            $details
        ]);

        return (int) $this->db->lastInsertId();
    }
public function createPostReport(
    int $reporterId,
    int $postId,
    string $reason,
    ?string $details = null
): ?int {
    if ($reason === "" || strlen($reason) > 100) {
        throw new InvalidArgumentException("Invalid report reason.");
    }

    $stmt = $this->db->prepare(
        "SELECT user_id FROM post WHERE post_id = ? LIMIT 1"
    );
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        throw new InvalidArgumentException("Post does not exist.");
    }

    if ((int) $post["user_id"] === $reporterId) {
        throw new InvalidArgumentException("You cannot report your own post.");
    }

    if ($this->hasAlreadyReported($reporterId, null, null, $postId)) {
        return null;
    }

    $details = trim($details ?? "");
    $details = $details === "" ? null : $details;

    $stmt = $this->db->prepare(
        "INSERT INTO reports (reporter_id, post_id, reason, details)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$reporterId, $postId, $reason, $details]);

    return (int) $this->db->lastInsertId();
}
}
