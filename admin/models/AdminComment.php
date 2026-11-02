<?php

class AdminComment
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getCommentById(int $commentId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT
                c.comment_id,
                c.comment,
                c.created_at,
                c.media_file,
                c.media_type,
                c.post_id,
                u.user_id,
                u.username,
                p.title AS post_title,
                p.created_at AS post_created_at
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
            LEFT JOIN post p ON c.post_id = p.post_id
            WHERE c.comment_id = :comment_id
            LIMIT 1
        ");

        $stmt->execute([
            "comment_id" => $commentId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}