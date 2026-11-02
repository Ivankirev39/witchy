
<?php

class LoginRateLimiter
{
    private PDO $db;

    private int $maxLoginAttempts = 5;
    private int $maxIpAttempts = 20;

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

        // Count all failed attempts from this IP,
        // including attempts using different usernames.
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS ip_attempts,

                SUM(
                    CASE WHEN login_key = ?
                    THEN 1 ELSE 0 END
                ) AS login_attempts

            FROM login_attempt

            WHERE ip_key = ?
              AND attempted_at >=
                  UTC_TIMESTAMP() - INTERVAL 15 MINUTE
        ");

        $stmt->execute([$loginKey, $ipKey]);

        $attempts = $stmt->fetch(PDO::FETCH_ASSOC);

        $ipAttempts = (int) ($attempts["ip_attempts"] ?? 0);
        $loginAttempts = (int) ($attempts["login_attempts"] ?? 0);

        return (
            $loginAttempts >= $this->maxLoginAttempts ||
            $ipAttempts >= $this->maxIpAttempts
        );
    }

    public function recordFailure(string $login, string $ip): void
    {
        [$loginKey, $ipKey] = $this->getKeys($login, $ip);

        $stmt = $this->db->prepare("
            INSERT INTO login_attempt
                (login_key, ip_key, attempted_at)
            VALUES (?, ?, UTC_TIMESTAMP())
        ");

        $stmt->execute([$loginKey, $ipKey]);
    }

    public function clearAttempts(string $login, string $ip): void
    {
        [$loginKey, $ipKey] = $this->getKeys($login, $ip);

        // Clear only the successful login's failures.
        // Other failed attempts from this IP remain.
        $stmt = $this->db->prepare("
            DELETE FROM login_attempt
            WHERE login_key = ?
              AND ip_key = ?
        ");

        $stmt->execute([$loginKey, $ipKey]);
    }
}
