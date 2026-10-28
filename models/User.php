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
}
