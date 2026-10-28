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
}
