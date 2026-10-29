<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Manage Users | Witchy";

$search = trim($_GET["search"] ?? "");
$users = [];
$error = "";

try {
    if ($search !== "") {
        $stmt = $conn->prepare("
            SELECT user_id, username, rank
            FROM users
            WHERE username LIKE ? OR email LIKE ?
            ORDER BY user_id DESC
        ");

        $term = "%" . $search . "%";
        $stmt->execute([$term, $term]);
    } else {
        $stmt = $conn->query("
            SELECT user_id, username, rank
            FROM users
            ORDER BY user_id DESC
        ");
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin users page failed: " . $e->getMessage());
    $error = "Users could not be loaded. Please try again later.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section>
    <h2>Manage Users</h2>
    <p class="admin-muted">
        View registered accounts and their current roles.
    </p>

    <form method="GET" action="users.php" class="admin-form">
        <label for="search">Search by username or email</label>
        <input
            type="search"
            id="search"
            name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            placeholder="Enter a username or email"
        >
        <button type="submit">Search</button>
        <?php if ($search !== ""): ?>
            <a href="users.php">Clear search</a>
        <?php endif; ?>
    </form>

    <?php if ($error !== ""): ?>
        <p class="admin-error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php else: ?>
        <p class="admin-muted">
            <?= count($users) ?> user(s) found.
        </p>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="3">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user["user_id"] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $user["username"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $user["rank"] ?? "user",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
