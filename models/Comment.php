<?php

class Comment
{
    private PDO $db;
    public function __construct(PDO $db)
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
                users.username,
                users.profile_image
            FROM comment
            JOIN users
                ON comment.user_id = users.user_id
            WHERE comment.post_id = ?
            ORDER BY comment.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$postId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createComment(
        int $postId,
        int $userId,
        string $comment,
        ?string $mediaFile = null,
        ?string $mediaType = null
    ): int {
        $sql = "
            INSERT INTO comment (
                user_id,
                post_id,
                comment,
                media_file,
                media_type
            )
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $postId,
            $comment,
            $mediaFile,
            $mediaType
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getCommentCountByPostId(int $postId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM comment
            WHERE post_id = ?
        ");
        $stmt->execute([$postId]);

        return (int) $stmt->fetchColumn();
    }

    public function getUsernameByUserId(int $userId): ?string
    {
        $stmt = $this->db->prepare("
            SELECT username
            FROM users
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        $username = $stmt->fetchColumn();

        return $username === false ? null : $username;
    }

    public function deleteComment(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare("
        DELETE FROM comment
        WHERE comment_id = ?
        AND user_id = ?
    ");

        $stmt->execute([$commentId, $userId]);

        return $stmt->rowCount() > 0;
    }
}