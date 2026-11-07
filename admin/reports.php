<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/models/AdminReport.php";

$pageTitle = "Reports | Witchy";
$pageCss = "admin_reports.css";

$reportModel = new AdminReport($conn);

$errorMessage = "";

$reportCounts = [];
$reports = [];
$totalReports = 0;
$totalPages = 1;

// Pagination settings.
$limit = 10;

// Validate the requested page number.
$page = filter_var(
    $_GET["page"] ?? 1,
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;

// Validate filters.
$status = $_GET["status"] ?? "";
$type = $_GET["type"] ?? "";
$search = $_GET["search"] ?? "";

// Only accept simple string values.
$status = is_string($status) ? $status : "";
$type = is_string($type) ? $type : "";
$search = is_string($search) ? trim($search) : "";

// Allow only the statuses that exist in the database.
$allowedStatuses = ["pending", "reviewed", "dismissed"];

if (!in_array($status, $allowedStatuses, true)) {
    $status = "";
}

// Allow only supported report types.
$allowedTypes = ["user", "comment"];

if (!in_array($type, $allowedTypes, true)) {
    $type = "";
}

// Avoid excessively long search queries.
$search = substr($search, 0, 100);

// Build the filters array for the model.
$filters = [
    "status" => $status,
    "type" => $type,
    "search" => $search
];

try {
    // Retrieve overall statistics.
    $reportCounts = $reportModel->getReportCounts();

    // Count reports matching the selected filters.
    $totalReports = $reportModel->getFilteredReportCount($filters);

    // Calculate pagination.
    $totalPages = max(1, (int) ceil($totalReports / $limit));

    // Prevent requests for pages that do not exist.
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $limit;

    // Retrieve the reports for the current page.
    $reports = $reportModel->getReports(
        $filters,
        $limit,
        $offset
    );

} catch (Throwable $e) {
    error_log("Admin reports error: " . $e->getMessage());
    $errorMessage = "Something went wrong while loading reports.";
}

require_once __DIR__ . "/includes/admin_header.php";

?>

<section class="admin-reports">

    <div class="reports-heading">
        <div>
            <h2>Reports</h2>
            <p>Review and manage reports submitted by Witchy users.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?>

        <p class="reports-error">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </p>

    <?php else: ?>

        <!-- REPORT FILTERS -->

        <form method="GET" action="<?= BASE_URL ?>admin/reports.php" class="reports-filters">

        <div class="filter-group">
            <label for="status">Status</label>

            <select name="status" id="status">
                <option value="">All statuses</option>
                <option value="pending" <?= $status === "pending" ? "selected" : "" ?>>
                    Pending
                </option>
                <option value="reviewed" <?= $status === "reviewed" ? "selected" : "" ?>>
                    Reviewed
                </option>
                <option value="dismissed" <?= $status === "dismissed" ? "selected" : "" ?>>
                    Dismissed
                </option>
            </select>
        </div>

        <div class="filter-group">
            <label for="type">Report type</label>

            <select name="type" id="type">
                <option value="">All types</option>
                <option value="user" <?= $type === "user" ? "selected" : "" ?>>
                    Profile
                </option>
                <option value="comment" <?= $type === "comment" ? "selected" : "" ?>>
                    Comment
                </option>
            </select>
        </div>

        <div class="filter-group">
            <label for="search">Search</label>

            <input
                type="search"
                name="search"
                id="search"
                placeholder="Report ID or username"
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            >
        </div>

        <button type="submit">Apply filters</button>

        <a href="<?= BASE_URL ?>admin/reports.php">Clear filters</a>

        </form>

        <!-- REPORT STATISTICS -->

        <div class="report-stats">

            <div class="report-stat-card">
                <span>Total reports</span>
                <strong><?= (int) ($reportCounts["total"] ?? 0) ?></strong>
            </div>

            <div class="report-stat-card">
                <span>Pending</span>
                <strong><?= (int) ($reportCounts["pending"] ?? 0) ?></strong>
            </div>

            <div class="report-stat-card">
                <span>Reviewed</span>
                <strong><?= (int) ($reportCounts["reviewed"] ?? 0) ?></strong>
            </div>

            <div class="report-stat-card">
                <span>Dismissed</span>
                <strong><?= (int) ($reportCounts["dismissed"] ?? 0) ?></strong>
            </div>

        </div>

        <!-- REPORTS TABLE -->

        <div class="reports-table-container">

            <div class="reports-table-heading">
                <h3>Submitted reports</h3>
                <span><?= $totalReports ?> reports</span>
            </div>

            <?php if (empty($reports)): ?>

                <p class="no-reports">
                    There are currently no reports.
                </p>

            <?php else: ?>

                <table class="reports-table">

                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Reporter</th>
                            <th>Reported content</th>
                            <th>Reason</th>
                            <th>Date submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($reports as $report): ?>

                            <?php
                                $isCommentReport = !empty($report["comment_id"]);

                                $target = $isCommentReport
                                    ? "Comment #" . (int) $report["comment_id"]
                                    : "@" . ($report["reported_username"] ?? "Unknown user");

                                $status = $report["status"] ?? "pending";
                            ?>

                            <tr>

                                <td>
                                    #<?= (int) $report["report_id"] ?>
                                </td>

                                <td>
                                    @<?= htmlspecialchars(
                                        $report["reporter_username"] ?? "Unknown",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <span class="report-type">
                                        <?= $isCommentReport ? "Comment" : "Profile" ?>
                                    </span>

                                    <span>
                                        <?= htmlspecialchars($target, ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $report["reason"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        date("d M Y, H:i", strtotime($report["created_at"])),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <span class="report-status status-<?= htmlspecialchars(
                                        $status,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>">
                                        <?= htmlspecialchars(
                                            ucfirst($status),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="<?= BASE_URL ?>admin/view_report.php?id=<?= (int) $report["report_id"] ?>" class="view-report-link">
                                        View report
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

            <?php if ($totalPages > 1): ?>

            <div class="reports-pagination">

                <?php if ($page > 1): ?>

                    <a href="<?= BASE_URL ?>admin/reports.php?<?= htmlspecialchars(
                        http_build_query(array_merge($filters, ["page" => $page - 1])),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>">
                        Previous
                    </a>

                <?php endif; ?>

                <span>
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>

                    <a href="<?= BASE_URL ?>admin/reports.php?<?= htmlspecialchars(
                        http_build_query(array_merge($filters, ["page" => $page + 1])),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>">
                        Next
                    </a>

                <?php endif; ?>

            </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
