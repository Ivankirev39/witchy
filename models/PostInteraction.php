<?php

class PostInteraction
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function toggleLike(int $userId, int $postId): array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT post_id
                FROM post
                WHERE post_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$postId]);

            if ($stmt->fetchColumn() === false) {
                throw new RuntimeException("Post not found.");
            }

            $stmt = $this->db->prepare("
                SELECT 1
                FROM post_like
                WHERE user_id = ? AND post_id = ?
            ");

            $stmt->execute([$userId, $postId]);

            $liked = $stmt->fetchColumn() !== false;

            if ($liked) {
                $stmt = $this->db->prepare("
                    DELETE FROM post_like
                    WHERE user_id = ? AND post_id = ?
                ");

                $stmt->execute([$userId, $postId]);

                $liked = false;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO post_like (user_id, post_id)
                    VALUES (?, ?)
                ");

                $stmt->execute([$userId, $postId]);

                $liked = true;
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM post_like
                WHERE post_id = ?
            ");

            $stmt->execute([$postId]);

            $likeCount = (int) $stmt->fetchColumn();

            $this->db->commit();

            return [
                "liked" => $liked,
                "like_count" => $likeCount
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function toggleSave(int $userId, int $postId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT post_id
                FROM post
                WHERE post_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$postId]);

            if ($stmt->fetchColumn() === false) {
                throw new RuntimeException("Post not found.");
            }

            $stmt = $this->db->prepare("
                SELECT 1
                FROM save
                WHERE user_id = ? AND post_id = ?
            ");

            $stmt->execute([$userId, $postId]);

            if ($stmt->fetchColumn() !== false) {
                $stmt = $this->db->prepare("
                    DELETE FROM save
                    WHERE user_id = ? AND post_id = ?
                ");

                $stmt->execute([$userId, $postId]);

                $saved = false;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO save (user_id, post_id)
                    VALUES (?, ?)
                ");

                $stmt->execute([$userId, $postId]);

                $saved = true;
            }

            $this->db->commit();

            return $saved;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function removeLike(int $userId, int $postId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM post_like
            WHERE user_id = ? AND post_id = ?
        ");

        $stmt->execute([$userId, $postId]);
    }
}