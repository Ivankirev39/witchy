<?php

class PostFeed
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getFeedPosts(
        int $userId,
        bool $followingOnly = false,
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                post.*,
                users.username AS author_username,
                users.profile_image AS author_profile_image,

                v.like_count,
                v.comment_count,

                EXISTS(
                    SELECT 1
                    FROM post_like
                    WHERE post_like.post_id = post.post_id
                    AND post_like.user_id = ?
                ) AS user_liked,

                EXISTS(
                    SELECT 1
                    FROM save
                    WHERE save.post_id = post.post_id
                    AND save.user_id = ?
                ) AS user_saved,

                EXISTS(
                    SELECT 1
                    FROM follow
                    WHERE follow.follower_id = ?
                    AND follow.following_id = post.user_id
                ) AS user_follows

            FROM post

            JOIN users
                ON users.user_id = post.user_id

            JOIN v_post_engagement v
                ON v.post_id = post.post_id
        ";

        $params = [$userId, $userId, $userId];

        if ($followingOnly) {
            $sql .= "
                WHERE EXISTS (
                    SELECT 1
                    FROM follow
                    WHERE follow.follower_id = ?
                    AND follow.following_id = post.user_id
                )
            ";

            $params[] = $userId;
        }

        $sql .= " ORDER BY post.is_sticky DESC, post.created_at DESC";

        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

     public function getPostMediaForPosts(array $postIds): array
    {
        $postIds = array_values(array_filter(
            array_map("intval", $postIds),
            static fn(int $postId): bool => $postId > 0
        ));

        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(",", array_fill(0, count($postIds), "?"));

        $stmt = $this->db->prepare("
        SELECT post_id, media_id, file, media_type, sort_order
        FROM post_media
        WHERE post_id IN ($placeholders)
        ORDER BY post_id ASC, sort_order ASC, media_id ASC
    ");
        $stmt->execute($postIds);

        $mediaByPost = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $media) {
            $mediaByPost[(int) $media["post_id"]][] = $media;
        }

        return $mediaByPost;
    }

}




    