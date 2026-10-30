<?php

class Comment
{
    private PDO $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCommentsByPostId(int $postId, int $limit = 5, int $offset = 0): array
    {
        $sql = "
            SELECT
                v.comment_id,
                v.post_id,
                v.user_id,
                v.comment,
                v.media_file,
                v.media_type,
                v.created_at,
                v.author_username AS username,
                users.profile_image
            FROM v_latest_comments v
            INNER JOIN users
                ON users.user_id = v.user_id
            WHERE v.post_id = ?
            ORDER BY v.created_at ASC, v.comment_id ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(1, $postId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);

        $stmt->execute();

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

    public function deleteComment(int $commentId, int $userId): ?array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
            SELECT media_file
            FROM comment
            WHERE comment_id = ? AND user_id = ?
            FOR UPDATE
        ");
            $stmt->execute([$commentId, $userId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($comment === false) {
                $this->db->rollBack();
                return null;
            }

            $stmt = $this->db->prepare("
            DELETE FROM comment
            WHERE comment_id = ? AND user_id = ?
        ");
            $stmt->execute([$commentId, $userId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException("Comment was not deleted.");
            }

            $this->db->commit();

            return $comment;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
    public function getCommentsForPosts(array $postIds, int $perPost = 5): array
    {
        $postIds = array_values(array_filter(
            array_map("intval", $postIds),
            static fn(int $postId): bool => $postId > 0
        ));

        if ($postIds === []) {
            return [];
        }

        $perPost = max(1, min($perPost, 50));
        $placeholders = implode(",", array_fill(0, count($postIds), "?"));

        $sql = "
        SELECT
            comment_id,
            post_id,
            user_id,
            comment,
            media_file,
            media_type,
            created_at,
            username
        FROM (
            SELECT
                v.comment_id,
                v.post_id,
                v.user_id,
                v.comment,
                v.media_file,
                v.media_type,
                v.created_at,
                v.author_username AS username,
                ROW_NUMBER() OVER (
                    PARTITION BY v.post_id
                    ORDER BY v.created_at ASC, v.comment_id ASC
                ) AS comment_rank
            FROM v_latest_comments v
            WHERE v.post_id IN ($placeholders)
        ) ranked_comments
        WHERE comment_rank <= ?
        ORDER BY post_id ASC, created_at ASC, comment_id ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$postIds, $perPost]);

        $commentsByPost = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
            $commentsByPost[(int) $comment["post_id"]][] = $comment;
        }

        return $commentsByPost;
    }
}
