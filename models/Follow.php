<?php

class Follow
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Check if a user follows another user
    public function isFollowing(int $userId, int $targetId): bool
    {
        $sql = "
            SELECT 1
            FROM follow
            WHERE follower_id = ? AND following_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $targetId]);

        return $stmt->fetchColumn() !== false;
    }

    // Follow another user
    public function followUser(int $userId, int $targetId): bool
    {
        $sql = "
            INSERT IGNORE INTO follow (follower_id, following_id)
            VALUES (?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $targetId]);

        return $stmt->rowCount() > 0;
    }

    // Unfollow a user
    public function unfollowUser(int $userId, int $targetId): bool
    {
        $sql = "
            DELETE FROM follow
            WHERE follower_id = ? AND following_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $targetId]);

        return $stmt->rowCount() > 0;
    }

    // Count followers
    public function countFollowers(int $userId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM follow
            WHERE following_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    // Count users being followed
    public function countFollowing(int $userId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM follow
            WHERE follower_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }
}
