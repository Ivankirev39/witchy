<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
$pageTitle = "Notifications | Witchy";
$pageCss = "notifications.css";
$user_id = (int) $_SESSION["user_id"];
$filter = ($_GET["filter"] ?? "all") === "unread" ? "unread" : "all";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }
    $action = $_POST["action"] ?? "";
    if ($action === "read") {
        $notification_id = (int) ($_POST["notification_id"] ?? 0);
        $stmt = $conn->prepare("
            UPDATE notification
            SET is_read = 1
            WHERE notification_id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $user_id]);
    } elseif ($action === "read_all") {
        $stmt = $conn->prepare("
            UPDATE notification
            SET is_read = 1
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
    } else {
        http_response_code(400);
        exit("Invalid action.");
    }
    header("Location: notifications.php?filter=" . $filter);
    exit;
}
$countStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM notification
    WHERE user_id = ? AND is_read = 0
");
$countStmt->execute([$user_id]);
$unreadCount = (int) $countStmt->fetchColumn();
$sql = "
    SELECT n.notification_id, n.type, n.post_id, n.is_read, n.created_at,
           u.user_id AS actor_id, u.username, u.profile_image
    FROM notification n
    INNER JOIN users u ON u.user_id = n.actor_id
    WHERE n.user_id = ?
";
if ($filter === "unread") {
    $sql .= " AND n.is_read = 0";
}
$sql .= " ORDER BY n.created_at DESC, n.notification_id DESC LIMIT 100";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . "/includes/user_header.php";
?>
<section class="notifications-page">
    <header class="notifications-header">
        <div>
            <h1>Notifications</h1>
            <p>Keep up with what's happening in your community.</p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="notifications.php?filter=<?= $filter ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                <input type="hidden" name="action" value="read_all">
                <button type="submit" class="notifications-read-all">Mark all as read</button>
            </form>
        <?php endif; ?>
    </header>
    <nav class="notifications-filters" aria-label="Notification filters">
        <a href="notifications.php?filter=all" class="<?= $filter === "all" ? "active" : "" ?>">All</a>
        <a href="notifications.php?filter=unread" class="<?= $filter === "unread" ? "active" : "" ?>">
            Unread <span><?= $unreadCount ?></span>
        </a>
    </nav>
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="notifications-empty">
                <span class="notifications-empty-icon">✦</span>
                <h2><?= $filter === "unread" ? "You're all caught up!" : "No notifications yet" ?></h2>
                <p><?= $filter === "unread" ? "You have no unread notifications." : "When someone interacts with your posts or follows you, you'll see it here." ?></p>
                <a href="feed.php">Explore the community</a>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                $type = $notification["type"];
                $actor = htmlspecialchars($notification["username"], ENT_QUOTES, "UTF-8");
                $actorId = (int) $notification["actor_id"];
                $postId = (int) $notification["post_id"];
                $message = match ($type) {
                    "like" => "liked your post",
                    "comment" => "commented on your post",
                    "follow" => "started following you",
                    default => "interacted with you"
                };
                $symbol = match ($type) {
                    "like" => "♥",
                    "comment" => "✎",
                    "follow" => "✦",
                    default => "✦"
                };
                $target = $type === "follow" || $postId <= 0
                    ? "profile.php?id=" . $actorId
                    : "post.php?id=" . $postId;
                $avatar = !empty($notification["profile_image"])
                    ? $notification["profile_image"]
                    : "images/default-profile.jpg";
                ?>
                <article class="notification-item <?= !$notification["is_read"] ? "unread" : "" ?>">
                    <a href="<?= htmlspecialchars($target, ENT_QUOTES, "UTF-8") ?>" class="notification-content">
                        <div class="notification-avatar">
                            <img src="/witchy/<?= htmlspecialchars($avatar, ENT_QUOTES, "UTF-8") ?>" alt="">
                            <span class="notification-type"><?= $symbol ?></span>
                        </div>
                        <div class="notification-details">
                            <p><strong><?= $actor ?></strong> <?= $message ?>.</p>
                            <time datetime="<?= htmlspecialchars($notification["created_at"], ENT_QUOTES, "UTF-8") ?>">
                                <?= htmlspecialchars(date("j M Y, H:i", strtotime($notification["created_at"])), ENT_QUOTES, "UTF-8") ?>
                            </time>
                        </div>
                    </a>
                    <?php if (!$notification["is_read"]): ?>
                        <form method="POST" action="notifications.php?filter=<?= $filter ?>" class="notification-read-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="action" value="read">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification["notification_id"] ?>">
                            <button type="submit" class="notification-read-button" title="Mark as read" aria-label="Mark as read">
                                <span></span>
                            </button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
