<?php

class Comment
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getCommentsByPostId(int $postId): array
    {
        $sql = "
            SELECT
                comment.comment_id,
                comment.post_id,
                comment.user_id,
                comment.comment,
                comment.media_file,
                comment.media_type,
                comment.created_at,
                users.username
            FROM comment
            JOIN users
                ON comment.user_id = users.user_id
            WHERE comment.post_id = ?
            ORDER BY comment.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();

        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $comments;
    }
}
