<?php

class Comment
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // Retrieve all comments belonging to a post
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
        $stmt->bind_param("i", $postId);
        $stmt->execute();

        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $comments;
    }

    // Create a comment and return its ID
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

        $stmt->bind_param(
            "iisss",
            $userId,
            $postId,
            $comment,
            $mediaFile,
            $mediaType
        );

        $stmt->execute();
        $commentId = $this->db->insert_id;

        $stmt->close();

        return $commentId;
    }

    // Count comments belonging to a post
    public function getCommentCountByPostId(int $postId): int
    {
        $sql = "
            SELECT COUNT(*) AS comment_count
            FROM comment
            WHERE post_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return (int) $result["comment_count"];
    }

    // Retrieve a user's username
    public function getUsernameByUserId(int $userId): ?string
    {
        $sql = "
            SELECT username
            FROM users
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return $result["username"] ?? null;
    }

    // Delete a comment only if it belongs to the specified user
    public function deleteComment(
        int $commentId,
        int $userId
    ): bool {
        $sql = "
            DELETE FROM comment
            WHERE comment_id = ?
            AND user_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $commentId, $userId);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }
}
