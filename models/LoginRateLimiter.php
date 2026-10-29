
<?php

class LoginRateLimiter
{
    private PDO $db;
    private int $maxAttempts = 5;
    private int $windowMinutes = 15;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function getKeys(string $login, string $ip): array
    {
        return [
            hash("sha256", strtolower(trim($login))),
            hash("sha256", $ip)
        ];
    }

    public function isLimited(string $login, string $ip): bool
    {
        [$loginKey, $ipKey] = $this->getKeys($login, $ip);

        $sql = "
            SELECT COUNT(*)
            FROM login_attempt
            WHERE login_key = ?
              AND ip_key = ?
              AND attempted_at >= DATE_SUB(
                  UTC_TIMESTAMP(),
                  INTERVAL 15 MINUTE
              )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loginKey, $ipKey]);

        return (int) $stmt->fetchColumn() >= $this->maxAttempts;
    }

    public function recordFailure(string $login, string $ip): void
    {
        [$loginKey, $ipKey] = $this->getKeys($login, $ip);

        $sql = "
            INSERT INTO login_attempt
                (login_key, ip_key, attempted_at)
            VALUES (?, ?, UTC_TIMESTAMP())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loginKey, $ipKey]);
    }

    public function clearAttempts(string $login, string $ip): void
    {
        [$loginKey, $ipKey] = $this->getKeys($login, $ip);

        $sql = "
            DELETE FROM login_attempt
            WHERE login_key = ?
              AND ip_key = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loginKey, $ipKey]);
    }
}
