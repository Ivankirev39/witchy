
<?php

class Comment
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCommentsByPostId(
        int $postId,
        int $limit = 5,
        int $offset = 0
    ): array {
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
            INNER JOIN comment c
                ON c.comment_id = v.comment_id
            INNER JOIN users
                ON users.user_id = v.user_id
            WHERE v.post_id = ?
              AND c.parent_comment_id IS NULL
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

    public function getCommentCountByPostId(int $postId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM comment
            WHERE post_id = ?
              AND deleted_at IS NULL
        ");

        $stmt->execute([$postId]);

        return (int) $stmt->fetchColumn();
    }

    public function getCommentsForPosts(
        array $postIds,
        int $perPost = 5,
        int $userId = 0
    ): array {
        $postIds = array_values(array_filter(
            array_map("intval", $postIds),
            static fn(int $postId): bool => $postId > 0
        ));
    
        if ($postIds === []) {
            return [];
        }
    
        $perPost = max(1, min($perPost, 50));
        $placeholders = implode(
            ",",
            array_fill(0, count($postIds), "?")
        );
    
        $sql = "
            SELECT
                comment_id,
                post_id,
                user_id,
                comment,
                media_file,
                media_type,
                created_at,
                username,
                profile_image,
                reply_count,
                like_count,
                user_liked
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
                    u.profile_image,
                    (
                        SELECT COUNT(*)
                        FROM comment replies
                        WHERE replies.parent_comment_id = v.comment_id
                          AND replies.deleted_at IS NULL
                    ) AS reply_count,
                    (
                        SELECT COUNT(*)
                        FROM comment_like cl
                        WHERE cl.comment_id = v.comment_id
                    ) AS like_count,
                    (
                        SELECT COUNT(*)
                        FROM comment_like cl
                        WHERE cl.comment_id = v.comment_id
                          AND cl.user_id = ?
                    ) AS user_liked,
                    ROW_NUMBER() OVER (
                        PARTITION BY v.post_id
                        ORDER BY v.created_at ASC, v.comment_id ASC
                    ) AS comment_rank
                FROM v_latest_comments v
                INNER JOIN comment c
                    ON c.comment_id = v.comment_id
                INNER JOIN users u
                    ON u.user_id = v.user_id
                WHERE v.post_id IN ($placeholders)
                  AND c.parent_comment_id IS NULL
            ) ranked_comments
            WHERE comment_rank <= ?
            ORDER BY post_id ASC, created_at ASC, comment_id ASC
        ";
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, ...$postIds, $perPost]);
    
        $commentsByPost = [];
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
            $commentsByPost[(int) $comment["post_id"]][] = $comment;
        }
    
        return $commentsByPost;
    
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

    public function toggleCommentLike(
        int $commentId,
        int $userId
    ): array {
        $this->db->beginTransaction();

        try {
            // Check that the comment exists and is not deleted.
            $stmt = $this->db->prepare("
                SELECT comment_id
                FROM comment
                WHERE comment_id = ?
                  AND deleted_at IS NULL
                FOR UPDATE
            ");

            $stmt->execute([$commentId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException("Comment not found.");
            }

            // Check whether the user already liked the comment.
            $stmt = $this->db->prepare("
                SELECT user_id
                FROM comment_like
                WHERE comment_id = ?
                  AND user_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$commentId, $userId]);

            $alreadyLiked = (bool) $stmt->fetchColumn();

            if ($alreadyLiked) {
                // Remove existing like.
                $stmt = $this->db->prepare("
                    DELETE FROM comment_like
                    WHERE comment_id = ?
                      AND user_id = ?
                ");

                $stmt->execute([$commentId, $userId]);

                $liked = false;
            } else {
                // Create new like.
                $stmt = $this->db->prepare("
                    INSERT INTO comment_like (
                        user_id,
                        comment_id
                    )
                    VALUES (?, ?)
                ");

                $stmt->execute([$userId, $commentId]);

                $liked = true;
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM comment_like
                WHERE comment_id = ?
            ");

            $stmt->execute([$commentId]);

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

    public function getCommentLikeCount(int $commentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM comment_like
            WHERE comment_id = ?
        ");

        $stmt->execute([$commentId]);

        return (int) $stmt->fetchColumn();
    }

    public function hasUserLikedComment(
        int $commentId,
        int $userId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM comment_like
            WHERE comment_id = ?
              AND user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$commentId, $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function createReply(
        int $parentCommentId,
        int $postId,
        int $userId,
        string $comment
    ): int {
        $stmt = $this->db->prepare("
            SELECT comment_id
            FROM comment
            WHERE comment_id = ?
              AND post_id = ?
              AND parent_comment_id IS NULL
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            $parentCommentId,
            $postId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                "The comment you are replying to is unavailable."
            );
        }

        $stmt = $this->db->prepare("
            INSERT INTO comment (
                user_id,
                post_id,
                parent_comment_id,
                comment
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $postId,
            $parentCommentId,
            $comment
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getRepliesByCommentId(
        int $parentCommentId
    ): array {
        $sql = "
            SELECT
                c.comment_id,
                c.post_id,
                c.user_id,
                c.parent_comment_id,
                c.comment,
                c.media_file,
                c.media_type,
                c.created_at,
                u.username,
                u.profile_image
            FROM comment c
            INNER JOIN users u
                ON u.user_id = c.user_id
            WHERE c.parent_comment_id = ?
              AND c.deleted_at IS NULL
            ORDER BY c.created_at ASC, c.comment_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentCommentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReplyCountByCommentId(int $parentCommentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM comment
            WHERE parent_comment_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$parentCommentId]);

        return (int) $stmt->fetchColumn();
    }

    public function getRepliesByCommentIdPaginated(
        int $parentCommentId,
        int $limit = 3,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 20));
        $offset = max(0, $offset);

        $sql = "
            SELECT
                c.comment_id,
                c.post_id,
                c.user_id,
                c.parent_comment_id,
                c.comment,
                c.media_file,
                c.media_type,
                c.created_at,
                u.username,
                u.profile_image
            FROM comment c
            INNER JOIN users u
                ON u.user_id = c.user_id
            WHERE c.parent_comment_id = ?
            AND c.deleted_at IS NULL
            ORDER BY c.created_at ASC, c.comment_id ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(1, $parentCommentId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getProfileImageByUserId(int $userId): ?string
    {
        $stmt = $this->db->prepare("
            SELECT profile_image
            FROM users
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);

        $profileImage = $stmt->fetchColumn();

        return $profileImage === false ? null : $profileImage;
    }

    public function getUserDetailsByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT username, profile_image
            FROM users
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }


    public function deleteComment(
        int $commentId,
        int $userId
    ): ?array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT media_file
                FROM comment
                WHERE comment_id = ?
                  AND user_id = ?
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
                WHERE comment_id = ?
                  AND user_id = ?
            ");

            $stmt->execute([$commentId, $userId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    "Comment was not deleted."
                );
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
}
