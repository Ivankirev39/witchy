<?php

class Post
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


  public function getFeedPosts(
        int $userId,
        bool $followingOnly = false,
        int $limit = 10,
        int $offset = 0
    ): array
    {
        $sql = "
            SELECT
                post.*,
                users.username AS author_username,
                users.profile_image AS author_profile_image,

                v.like_count,
                v.comment_count,

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

            JOIN users
                ON users.user_id = post.user_id

            JOIN v_post_engagement v
                ON v.post_id = post.post_id
        ";

        $params = [$userId, $userId, $userId];

        if ($followingOnly) {
            $sql .= "
                WHERE EXISTS (
                    SELECT 1
                    FROM follow
                    WHERE follow.follower_id = ?
                    AND follow.following_id = post.user_id
                )
            ";

            $params[] = $userId;
        }

       $sql .= " ORDER BY post.created_at DESC";

        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
    public function getPostOwnerId(int $postId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT user_id
            FROM post
            WHERE post_id = ?
        ");
        $stmt->execute([$postId]);

        $ownerId = $stmt->fetchColumn();

        return $ownerId === false ? null : (int) $ownerId;
    }
    public function getLikedPosts(
        int $userId,
        string $topic,
        string $sort
    ): array {
        $orderByOptions = [
            "newest_post" => "p.created_at DESC",
            "oldest_post" => "p.created_at ASC",
            "title_asc" => "p.title ASC"
        ];

        $orderBy = $orderByOptions[$sort] ?? $orderByOptions["newest_post"];

        $sql = "
            SELECT
                p.post_id,
                p.title,
                p.description,
                p.topic,
                p.image,
                p.created_at,
                u.username,
                pm.file AS media_file,
                pm.media_type
            FROM post_like pl
            INNER JOIN post p ON p.post_id = pl.post_id
            INNER JOIN users u ON u.user_id = p.user_id
            LEFT JOIN post_media pm ON pm.media_id = (
                SELECT pm2.media_id
                FROM post_media pm2
                WHERE pm2.post_id = p.post_id
                ORDER BY pm2.sort_order ASC, pm2.media_id ASC
                LIMIT 1
            )
            WHERE pl.user_id = ?
        ";

        $params = [$userId];

        if ($topic !== "") {
            $sql .= " AND p.topic = ?";
            $params[] = $topic;
        }

        $sql .= " ORDER BY " . $orderBy;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSavedPosts(
        int $userId,
        string $topic,
        string $sort
    ): array {
        $orderByOptions = [
            "newest_saved" => "s.created_at DESC",
            "oldest_saved" => "s.created_at ASC",
            "newest_post" => "p.created_at DESC",
            "oldest_post" => "p.created_at ASC",
            "title_asc" => "p.title ASC"
        ];

        $orderBy = $orderByOptions[$sort] ?? $orderByOptions["newest_saved"];

        $sql = "
            SELECT
                p.post_id,
                p.user_id,
                p.title,
                p.description,
                p.topic,
                p.created_at,
                u.username,
                pm.file AS media_file,
                pm.media_type
            FROM save s
            INNER JOIN post p ON p.post_id = s.post_id
            INNER JOIN users u ON u.user_id = p.user_id
            LEFT JOIN post_media pm ON pm.media_id = (
                SELECT pm2.media_id
                FROM post_media pm2
                WHERE pm2.post_id = p.post_id
                ORDER BY pm2.sort_order ASC, pm2.media_id ASC
                LIMIT 1
            )
            WHERE s.user_id = ?
        ";

        $params = [$userId];

        if ($topic !== "") {
            $sql .= " AND p.topic = ?";
            $params[] = $topic;
        }

        $sql .= " ORDER BY " . $orderBy;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeLike(int $userId, int $postId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM post_like
            WHERE user_id = ? AND post_id = ?
        ");
        $stmt->execute([$userId, $postId]);
    }
    public function deletePost(int $postId, int $userId): ?array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT post_id
                FROM post
                WHERE post_id = ? AND user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$postId, $userId]);

            if ($stmt->fetchColumn() === false) {
                $this->db->rollBack();
                return null;
            }

            $stmt = $this->db->prepare("
                SELECT file
                FROM post_media
                WHERE post_id = ?
            ");
            $stmt->execute([$postId]);
            $postFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $this->db->prepare("
                SELECT media_file
                FROM comment
                WHERE post_id = ? AND media_file IS NOT NULL
            ");
            $stmt->execute([$postId]);
            $commentFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $this->db->prepare("
                DELETE FROM post
                WHERE post_id = ? AND user_id = ?
            ");
            $stmt->execute([$postId, $userId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException("Post was not deleted.");
            }

            $this->db->commit();

            return [
                "posts" => $postFiles,
                "comments" => $commentFiles
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function searchExplorePosts(
        string $search,
        string $topic,
        string $sort,
        int $limit,
        int $offset
    ): array {
        $orderByOptions = [
            "newest" => "p.created_at DESC",
            "oldest" => "p.created_at ASC",
            "title_asc" => "p.title ASC",
            "title_desc" => "p.title DESC"
        ];

        $orderBy = $orderByOptions[$sort] ?? $orderByOptions["newest"];

        $sql = "
            SELECT
                p.post_id,
                p.user_id,
                p.title,
                p.description,
                p.topic,
                p.created_at,
                u.username,
                pm.file AS media_file,
                pm.media_type
            FROM post p
            INNER JOIN users u ON u.user_id = p.user_id
            LEFT JOIN post_media pm ON pm.media_id = (
                SELECT pm2.media_id
                FROM post_media pm2
                WHERE pm2.post_id = p.post_id
                ORDER BY pm2.sort_order ASC, pm2.media_id ASC
                LIMIT 1
            )
        ";

        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(
                p.title LIKE ?
                OR p.description LIKE ?
                OR p.topic LIKE ?
                OR u.username LIKE ?
            )";

            $term = "%" . $search . "%";
            array_push($params, $term, $term, $term, $term);
        }

        if ($topic !== "") {
            $conditions[] = "p.topic = ?";
            $params[] = $topic;
        }

        if ($conditions !== []) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY " . $orderBy . " LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countExplorePosts(string $search, string $topic): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM post p
            INNER JOIN users u ON u.user_id = p.user_id
        ";

        $conditions = [];
        $params = [];

        if ($search !== "") {
            $conditions[] = "(
                p.title LIKE ?
                OR p.description LIKE ?
                OR p.topic LIKE ?
                OR u.username LIKE ?
            )";

            $term = "%" . $search . "%";
            array_push($params, $term, $term, $term, $term);
        }

        if ($topic !== "") {
            $conditions[] = "p.topic = ?";
            $params[] = $topic;
        }

        if ($conditions !== []) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }


}