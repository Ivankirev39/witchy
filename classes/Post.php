<?php

class Post
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getFeedPosts(int $userId): array
    {
        $sql = "
            SELECT
                post.*,
                users.username AS author_username,
                users.profile_image AS author_profile_image,
                (
                    SELECT COUNT(*)
                    FROM post_like
                    WHERE post_like.post_id = post.post_id
                ) AS like_count,
                (
                    SELECT COUNT(*)
                    FROM comment
                    WHERE comment.post_id = post.post_id
                ) AS comment_count,
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
            JOIN users ON users.user_id = post.user_id
            ORDER BY post.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $userId, $userId, $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getPostMedia(int $postId): array
    {
        $sql = "
            SELECT media_id, file, media_type, sort_order
            FROM post_media
            WHERE post_id = ?
            ORDER BY sort_order ASC, media_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();

        $media = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $media;
    }
}
