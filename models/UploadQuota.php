<?php

class UploadQuota
{
    private PDO $db;

    private const MAX_DAILY_UPLOAD = 100 * 1024 * 1024; // Daily upload limit of 100 MB
    private const MAX_TOTAL_STORAGE = 500 * 1024 * 1024; // Total storage limit of 500 MB

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getDailyUploadUsage(int $userId): int
    {
        $stmt = $this->db->prepare("
        SELECT COALESCE(SUM(file_size), 0)
        FROM upload_history
        WHERE user_id = ?
        AND uploaded_at >= NOW() - INTERVAL 24 HOUR
    ");

        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public function getStorageUsage(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(pm.file_size), 0)
            FROM post_media pm
            INNER JOIN post p ON p.post_id = pm.post_id
            WHERE p.user_id = ?
        ");

        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public function canUpload(
        int $userId,
        int $newFileSize
    ): bool {
        $dailyUsage = $this->getDailyUploadUsage($userId);
        $storageUsage = $this->getStorageUsage($userId);

        if ($dailyUsage + $newFileSize > self::MAX_DAILY_UPLOAD) {
            return false;
        }

        if ($storageUsage + $newFileSize > self::MAX_TOTAL_STORAGE) {
            return false;
        }

        return true;
    }
    public function recordUploads(int $userId, array $fileSizes): void
    {
        $stmt = $this->db->prepare("
        INSERT INTO upload_history (user_id, file_size)
        VALUES (?, ?)
    ");

        foreach ($fileSizes as $fileSize) {
            $stmt->execute([$userId, $fileSize]);
        }
    }
}
