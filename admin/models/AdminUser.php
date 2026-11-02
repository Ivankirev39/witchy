<?php

class AdminUser
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /* Retrieve a user's account information */
    public function getUserById(int $userId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT
                user_id,
                username,
                email,
                birthdate,
                rank,
                bio,
                profile_image,
                cover_image
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* Retrieve a user's activity counts */
    public function getActivityCounts(int $userId): array
    {
        $queries = [
            "posts" => "SELECT COUNT(*) FROM post WHERE user_id = :user_id",
            "comments" => "SELECT COUNT(*) FROM comment WHERE user_id = :user_id",
            "followers" => "SELECT COUNT(*) FROM follow WHERE following_id = :user_id",
            "following" => "SELECT COUNT(*) FROM follow WHERE follower_id = :user_id"
        ];

        $counts = [];

        foreach ($queries as $key => $sql) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([":user_id" => $userId]);
            $counts[$key] = (int) $stmt->fetchColumn();
        }

        return $counts;
    }

    /* Retrieve a user's ten most recent posts */
    public function getRecentPosts(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                post_id,
                title,
                topic,
                created_at
            FROM post
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 10
        ");

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Retrieve a user's ten most recent comments */
    public function getRecentComments(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                c.comment_id,
                c.comment,
                c.created_at,
                c.post_id,
                p.title AS post_title
            FROM comment c
            LEFT JOIN post p ON c.post_id = p.post_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
            LIMIT 10
        ");

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Retrieve a user's currently active ban */
    public function getActiveBan(int $userId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT
                b.ban_id,
                b.reason,
                b.banned_at,
                b.expires_at,
                b.banned_by,
                admin.username AS banned_by_username
            FROM ban b
            LEFT JOIN users admin ON b.banned_by = admin.user_id
            WHERE b.user_id = :user_id
              AND b.revoked_at IS NULL
              AND b.banned_at <= NOW()
              AND (
                  b.expires_at IS NULL
                  OR b.expires_at > NOW()
              )
            ORDER BY b.banned_at DESC
            LIMIT 1
        ");

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* Retrieve a user's complete moderation history */
    public function getBanHistory(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                b.ban_id,
                b.reason,
                b.banned_at,
                b.expires_at,
                b.banned_by,
                issuing_admin.username AS banned_by_username,
                b.revoked_at,
                b.revoked_by,
                revoking_admin.username AS revoked_by_username,
                b.revocation_reason
            FROM ban b
            LEFT JOIN users issuing_admin
                ON b.banned_by = issuing_admin.user_id
            LEFT JOIN users revoking_admin
                ON b.revoked_by = revoking_admin.user_id
            WHERE b.user_id = :user_id
            ORDER BY b.banned_at DESC
        ");

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Create a new user ban */
    public function createBan(
        int $userId,
        int $adminId,
        string $reason,
        ?string $expiresAt
    ): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO ban (
                user_id,
                banned_by,
                reason,
                banned_at,
                expires_at
            )
            VALUES (
                :user_id,
                :banned_by,
                :reason,
                NOW(),
                :expires_at
            )
        ");

        return $stmt->execute([
            ":user_id" => $userId,
            ":banned_by" => $adminId,
            ":reason" => $reason,
            ":expires_at" => $expiresAt
        ]);
    }

    /* Revoke an active user ban */
    public function revokeBan(
        int $banId,
        int $userId,
        int $adminId,
        string $reason
    ): int {
        $stmt = $this->conn->prepare("
            UPDATE ban
            SET
                revoked_at = NOW(),
                revoked_by = :revoked_by,
                revocation_reason = :revocation_reason
            WHERE ban_id = :ban_id
              AND user_id = :user_id
              AND revoked_at IS NULL
              AND banned_at <= NOW()
              AND (
                  expires_at IS NULL
                  OR expires_at > NOW()
              )
        ");

        $stmt->execute([
            ":revoked_by" => $adminId,
            ":revocation_reason" => $reason,
            ":ban_id" => $banId,
            ":user_id" => $userId
        ]);

        return $stmt->rowCount();
    }
}
