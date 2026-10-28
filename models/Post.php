<?php

class Post
{
    private PDO $db;

    public function __construct(PDO $db)
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
        $stmt->execute([$userId, $userId, $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt->execute([$postId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
public function createPostWithMedia(
    int $userId,
    string $title,
    string $description,
    string $topic,
    string $sources,
    array $mediaRecords
): int {
    $this->db->beginTransaction();

    try {
        $stmt = $this->db->prepare("
            INSERT INTO post (user_id, title, description, topic, sources)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $title,
            $description,
            $topic,
            $sources
        ]);

        $postId = (int) $this->db->lastInsertId();

        if ($mediaRecords !== []) {
            $mediaStmt = $this->db->prepare("
                INSERT INTO post_media (post_id, file, media_type, sort_order)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($mediaRecords as $index => $media) {
                $mediaStmt->execute([
                    $postId,
                    $media["file"],
                    $media["media_type"],
                    $index + 1
                ]);
            }
        }

        $this->db->commit();

        return $postId;
    } catch (Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        throw $e;
    }
}
public function getPostById(int $postId, int $viewerId): ?array
{
    $stmt = $this->db->prepare("
        SELECT
            post.*,
            users.username,
            users.profile_image,
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
            EXISTS (
                SELECT 1
                FROM post_like
                WHERE post_like.post_id = post.post_id
                  AND post_like.user_id = ?
            ) AS user_liked,
            EXISTS (
                SELECT 1
                FROM save
                WHERE save.post_id = post.post_id
                  AND save.user_id = ?
            ) AS user_saved,
            EXISTS (
                SELECT 1
                FROM follow
                WHERE follow.follower_id = ?
                  AND follow.following_id = post.user_id
            ) AS user_follows
        FROM post
        INNER JOIN users ON users.user_id = post.user_id
        WHERE post.post_id = ?
    ");

    $stmt->execute([$viewerId, $viewerId, $viewerId, $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    return $post === false ? null : $post;
}
}