<?php

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Find user when logging in
    public function findByLogin(string $login): ?array
    {
        $sql = "
            SELECT user_id, username, password, rank
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$login, $login]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    // Check if username or email is taken
    public function checkUserExists(string $username, string $email): bool
    {
        $sql = "
            SELECT user_id
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $email]);

        return $stmt->fetchColumn() !== false;
    }

    // Add a new user
    public function createUser(string $username, string $email, string $password): int
    {
        $sql = "
            INSERT INTO users (username, email, password, rank)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $email, $password, "user"]);

        return (int) $this->db->lastInsertId();
    }

    // Get user information
    public function getUserById(int $userId): ?array
    {
        $sql = "
            SELECT username, email, birthdate, bio, profile_image, cover_image
            FROM users
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    // Check if username is taken by another user
    public function checkUsername(string $username, int $userId): bool
    {
        $sql = "
            SELECT user_id
            FROM users
            WHERE username = ? AND user_id != ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $userId]);

        return $stmt->fetchColumn() !== false;
    }

    // Save profile changes
    public function updateProfile(
        int $userId,
        string $username,
        ?string $birthdate,
        string $bio,
        ?string $profileImage,
        ?string $coverImage
    ): bool {
        $sql = "
            UPDATE users
            SET username = ?,
                birthdate = ?,
                bio = ?,
                profile_image = ?,
                cover_image = ?
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $username,
            $birthdate,
            $bio,
            $profileImage,
            $coverImage,
            $userId
        ]);
    }


    // Get information for public profiles
    public function getPublicProfile(int $userId): ?array
    {
        $sql = "
            SELECT username, rank, bio, profile_image, cover_image
            FROM users
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    
    // Delete an account and its related data
    public function deleteAccount(int $userId, string $password): ?array
    {
        $this->db->beginTransaction();

        try {
            // Check password and lock the account during deletion
            $stmt = $this->db->prepare("
                SELECT password, profile_image, cover_image
                FROM users
                WHERE user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user["password"])) {
                $this->db->rollBack();
                return null;
            }

            // Collect files before removing database records
            $files = [
                "profiles" => array_filter([
                    $user["profile_image"],
                    $user["cover_image"]
                ]),
                "posts" => [],
                "comments" => []
            ];

            // Images and videos from the user's posts
            $stmt = $this->db->prepare("
                SELECT post_media.file
                FROM post_media
                JOIN post ON post_media.post_id = post.post_id
                WHERE post.user_id = ?
            ");
            $stmt->execute([$userId]);
            $files["posts"] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Comment images written by this user or attached to their posts
            $stmt = $this->db->prepare("
                SELECT DISTINCT comment.media_file
                FROM comment
                WHERE (comment.user_id = ?
                    OR comment.post_id IN (
                        SELECT post_id FROM post WHERE user_id = ?
                    ))
                AND comment.media_file IS NOT NULL
            ");
            $stmt->execute([$userId, $userId]);
            $files["comments"] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Remove notifications involving this account or its posts
            $stmt = $this->db->prepare("
                DELETE FROM notification
                WHERE user_id = ?
                   OR actor_id = ?
                   OR post_id IN (
                       SELECT post_id FROM post WHERE user_id = ?
                   )
            ");
            $stmt->execute([$userId, $userId, $userId]);

            // Remove support requests connected to the account
            $stmt = $this->db->prepare("
                DELETE FROM support_request
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            // Other related records use ON DELETE CASCADE
            $stmt = $this->db->prepare("
                DELETE FROM users
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException("Account was not deleted.");
            }

            $this->db->commit();

            return $files;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}   
