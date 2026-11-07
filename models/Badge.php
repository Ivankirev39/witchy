<?php

class Badge
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Award a permanent badge only once.
    public function award(int $userId, string $badgeCode): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_badge (user_id, badge_id)
            SELECT ?, badge_id
            FROM badge
            WHERE code = ?
            ON DUPLICATE KEY UPDATE
                earned_at = earned_at
        ");

        $stmt->execute([$userId, $badgeCode]);
    }

    // Award Elder Moon after one year.
    public function checkElderMoon(int $userId): void
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM users
            WHERE user_id = ?
              AND created_at <= NOW() - INTERVAL 1 YEAR
        ");

        $stmt->execute([$userId]);

        if ($stmt->fetchColumn() !== false) {
            $this->award($userId, 'elder_moon');
        }
    }

    // Award Community Favorite after 50 received likes.
    public function checkCommunityFavorite(int $userId): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM post_like pl
            INNER JOIN post p
                ON p.post_id = pl.post_id
            WHERE p.user_id = ?
              AND pl.user_id != p.user_id
        ");

        $stmt->execute([$userId]);

        $totalLikes = (int) $stmt->fetchColumn();

        if ($totalLikes >= 10) {
            $this->award($userId, 'community_favorite');
        }
    }

    // Retrieve all badges visible for a user.
    public function getUserBadges(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.badge_id,
                b.code,
                b.name,
                b.description,
                b.image,
                ub.earned_at
            FROM users u
            CROSS JOIN badge b
            LEFT JOIN user_badge ub
                ON ub.user_id = u.user_id
                AND ub.badge_id = b.badge_id
            WHERE u.user_id = ?
              AND (
                  (
                      b.code = 'admin'
                      AND u.`rank` = 'admin'
                  )
                  OR
                  (
                      b.code = 'newbie'
                      AND u.created_at > NOW() - INTERVAL 30 DAY
                  )
                  OR
                  (
                      b.code NOT IN ('admin', 'newbie')
                      AND ub.badge_id IS NOT NULL
                  )
              )
            ORDER BY
                CASE b.code
                    WHEN 'admin' THEN 1
                    WHEN 'newbie' THEN 2
                    WHEN 'elder_moon' THEN 3
                    WHEN 'community_favorite' THEN 4
                    ELSE 5
                END
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
