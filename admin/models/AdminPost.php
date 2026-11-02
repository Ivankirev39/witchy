<?php

class AdminPost
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getPostById(int $postId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT
                p.post_id,
                p.user_id,
                p.title,
                p.description,
                p.image,
                p.topic,
                p.sources,
                p.is_hot,
                p.is_sticky,
                p.created_at,
                u.username
            FROM post p
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.post_id = :post_id
            LIMIT 1
        ");

        $stmt->execute(["post_id" => $postId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPostMedia(int $postId): array
    {
        $stmt = $this->conn->prepare("
            SELECT media_id, file, media_type, sort_order
            FROM post_media
            WHERE post_id = :post_id
            ORDER BY sort_order ASC, media_id ASC
        ");

        $stmt->execute(["post_id" => $postId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostComments(int $postId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                c.comment_id,
                c.comment,
                c.created_at,
                c.media_file,
                c.media_type,
                u.user_id,
                u.username
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at ASC
        ");

        $stmt->execute(["post_id" => $postId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEngagement(int $postId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM post_like pl
                    WHERE pl.post_id = p.post_id
                ) AS total_likes,

                (
                    SELECT COUNT(*)
                    FROM comment c
                    WHERE c.post_id = p.post_id
                ) AS total_comments,

                (
                    SELECT COUNT(*)
                    FROM save s
                    WHERE s.post_id = p.post_id
                ) AS total_saves,

                (
                    SELECT COUNT(*)
                    FROM post_like pl
                    WHERE pl.post_id = p.post_id
                      AND pl.liked_at >= NOW() - INTERVAL 48 HOUR
                ) AS recent_likes,

                (
                    SELECT COUNT(*)
                    FROM comment c
                    WHERE c.post_id = p.post_id
                      AND c.created_at >= NOW() - INTERVAL 48 HOUR
                ) AS recent_comments

            FROM post p
            WHERE p.post_id = :post_id
        ");

        $stmt->execute(["post_id" => $postId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $engagement = [
            "likes" => 0,
            "comments" => 0,
            "saves" => 0,
            "recent_likes" => 0,
            "recent_comments" => 0,
            "popularity_score" => 0
        ];

        if ($data) {
            $engagement["likes"] = (int) $data["total_likes"];
            $engagement["comments"] = (int) $data["total_comments"];
            $engagement["saves"] = (int) $data["total_saves"];
            $engagement["recent_likes"] = (int) $data["recent_likes"];
            $engagement["recent_comments"] = (int) $data["recent_comments"];

            $engagement["popularity_score"] =
                $engagement["likes"] + ($engagement["comments"] * 3);
        }

        return $engagement;
    }
}