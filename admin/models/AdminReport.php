<?php

class AdminReport
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getReportCounts(): array
    {
        $stmt = $this->conn->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'pending'), 0) AS pending,
                COALESCE(SUM(status = 'reviewed'), 0) AS reviewed,
                COALESCE(SUM(status = 'dismissed'), 0) AS dismissed
            FROM reports
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReports(
        array $filters = [],
        int $limit = 10,
        int $offset = 0
    ): array {
        $conditions = [];
        $params = [];

        if (!empty($filters["status"])) {
            $conditions[] = "r.status = :status";
            $params[":status"] = $filters["status"];
        }

        if (($filters["type"] ?? "") === "user") {
            $conditions[] = "r.reported_user_id IS NOT NULL";
            $conditions[] = "r.comment_id IS NULL";
            $conditions[] = "r.post_id IS NULL";
        } elseif (($filters["type"] ?? "") === "comment") {
            $conditions[] = "r.comment_id IS NOT NULL";
            $conditions[] = "r.reported_user_id IS NULL";
            $conditions[] = "r.post_id IS NULL";
        } elseif (($filters["type"] ?? "") === "post") {
            $conditions[] = "r.post_id IS NOT NULL";
            $conditions[] = "r.reported_user_id IS NULL";
            $conditions[] = "r.comment_id IS NULL";
        }

        if (!empty($filters["search"])) {
            $conditions[] = "(
                CAST(r.report_id AS CHAR) LIKE :report_search
                OR reporter.username LIKE :reporter_search
                OR reported_user.username LIKE :reported_search
                OR reported_post.title LIKE :post_search
            )";

            $searchTerm = "%" . $filters["search"] . "%";

            $params[":report_search"] = $searchTerm;
            $params[":reporter_search"] = $searchTerm;
            $params[":reported_search"] = $searchTerm;
            $params[":post_search"] = $searchTerm;
        }

        $where = $conditions
            ? "WHERE " . implode(" AND ", $conditions)
            : "";

        $sql = "
            SELECT
                r.report_id,
                r.reporter_id,
                r.reported_user_id,
                r.comment_id,
                r.post_id,
                r.reason,
                r.details,
                r.status,
                r.created_at,

                reporter.username AS reporter_username,
                reported_user.username AS reported_username,
                reported_post.title AS reported_post_title

            FROM reports r

            LEFT JOIN users reporter
                ON r.reporter_id = reporter.user_id

            LEFT JOIN users reported_user
                ON r.reported_user_id = reported_user.user_id

            LEFT JOIN post reported_post
                ON r.post_id = reported_post.post_id

            $where

            ORDER BY
                CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
                r.created_at ASC

            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredReportCount(array $filters = []): int
    {
        $conditions = [];
        $params = [];

        if (!empty($filters["status"])) {
            $conditions[] = "r.status = :status";
            $params[":status"] = $filters["status"];
        }

        if (($filters["type"] ?? "") === "user") {
            $conditions[] = "r.reported_user_id IS NOT NULL";
            $conditions[] = "r.comment_id IS NULL";
            $conditions[] = "r.post_id IS NULL";
        } elseif (($filters["type"] ?? "") === "comment") {
            $conditions[] = "r.comment_id IS NOT NULL";
            $conditions[] = "r.reported_user_id IS NULL";
            $conditions[] = "r.post_id IS NULL";
        } elseif (($filters["type"] ?? "") === "post") {
            $conditions[] = "r.post_id IS NOT NULL";
            $conditions[] = "r.reported_user_id IS NULL";
            $conditions[] = "r.comment_id IS NULL";
        }

        if (!empty($filters["search"])) {
            $conditions[] = "(
                CAST(r.report_id AS CHAR) LIKE :report_search
                OR reporter.username LIKE :reporter_search
                OR reported_user.username LIKE :reported_search
                OR reported_post.title LIKE :post_search
            )";

            $searchTerm = "%" . $filters["search"] . "%";

            $params[":report_search"] = $searchTerm;
            $params[":reporter_search"] = $searchTerm;
            $params[":reported_search"] = $searchTerm;
            $params[":post_search"] = $searchTerm;
        }

        $where = $conditions
            ? "WHERE " . implode(" AND ", $conditions)
            : "";

        $sql = "
            SELECT COUNT(*)
            FROM reports r

            LEFT JOIN users reporter
                ON r.reporter_id = reporter.user_id

            LEFT JOIN users reported_user
                ON r.reported_user_id = reported_user.user_id

            LEFT JOIN post reported_post
                ON r.post_id = reported_post.post_id

            $where
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getReportById(int $reportId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT
                r.report_id,
                r.reporter_id,
                r.reported_user_id,
                r.comment_id,
                r.post_id AS reported_post_id,
                r.reason,
                r.details,
                r.status,
                r.created_at,
                r.reviewed_at,
                r.reviewed_by,

                reporter.username AS reporter_username,
                reported_user.username AS reported_username,
                reviewer.username AS reviewer_username,

                c.comment AS reported_comment,
                c.created_at AS comment_created_at,
                c.media_file AS comment_media_file,
                c.media_type AS comment_media_type,
                c.post_id AS comment_post_id,
                c.deleted_at AS comment_deleted_at,

                comment_author.user_id AS comment_author_id,
                comment_author.username AS comment_author_username,

                comment_post.title AS comment_post_title,
                comment_post.created_at AS comment_post_created_at

            FROM reports r

            LEFT JOIN users reporter
                ON r.reporter_id = reporter.user_id

            LEFT JOIN users reported_user
                ON r.reported_user_id = reported_user.user_id

            LEFT JOIN users reviewer
                ON r.reviewed_by = reviewer.user_id

            LEFT JOIN comment c
                ON r.comment_id = c.comment_id

            LEFT JOIN users comment_author
                ON c.user_id = comment_author.user_id

            LEFT JOIN post comment_post
                ON c.post_id = comment_post.post_id

            WHERE r.report_id = :report_id
            LIMIT 1
        ");

        $stmt->execute([
            ":report_id" => $reportId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateReportStatus(
        int $reportId,
        string $status,
        int $adminId
    ): bool {
        if (!in_array($status, ["reviewed", "dismissed"], true)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE reports
            SET
                status = :status,
                reviewed_at = NOW(),
                reviewed_by = :reviewed_by
            WHERE report_id = :report_id
              AND status = 'pending'
        ");

        $stmt->execute([
            ":status" => $status,
            ":reviewed_by" => $adminId,
            ":report_id" => $reportId
        ]);

        return $stmt->rowCount() === 1;
    }
}
