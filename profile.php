<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
$pageTitle = "Profile | Witchy";
$user_id = (int) $_SESSION["user_id"];
// GET LOGGED-IN USER
$stmt = $conn->prepare("
    SELECT username, email, birthdate, rank, bio, profile_image, cover_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    die("User not found.");
}
// GET USER'S POSTS
$postStmt = $conn->prepare("
    SELECT post_id, title, description, topic, created_at
    FROM post
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$postStmt->bind_param("i", $user_id);
$postStmt->execute();
$posts = $postStmt->get_result();
$post_count = $posts->num_rows;
// PREPARE MEDIA QUERY
$mediaStmt = $conn->prepare("
    SELECT file, media_type
    FROM post_media
    WHERE post_id = ?
    ORDER BY sort_order ASC, media_id ASC
");
// FOLLOWER COUNT
$followerStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM follow
    WHERE following_id = ?
");
$followerStmt->bind_param("i", $user_id);
$followerStmt->execute();
$follower_count = $followerStmt->get_result()->fetch_assoc()["total"];
// FOLLOWING COUNT
$followingStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM follow
    WHERE follower_id = ?
");
$followingStmt->bind_param("i", $user_id);
$followingStmt->execute();
$following_count = $followingStmt->get_result()->fetch_assoc()["total"];
$pageCss = "profile.css";
require_once __DIR__ . "/includes/user_header.php";
?>
<section class="profile-page">
    <div class="profile-cover">

    <?php if (!empty($user["cover_image"])): ?>

        <img
            src="<?= htmlspecialchars($user["cover_image"], ENT_QUOTES, "UTF-8") ?>"
            alt=""
        >

    <?php else: ?>

        <span>WITCHY</span>

    <?php endif; ?>

</div>
    <section class="profile-header">
        <div class="profile-avatar">
    <?php if (!empty($user["profile_image"])): ?>
        <img
            src="<?= htmlspecialchars($user["profile_image"], ENT_QUOTES, "UTF-8") ?>"
            alt="<?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>'s profile picture"
            class="profile-avatar-image"
            id="profile-avatar-image"
        >
    <?php else: ?>
        <span><?= htmlspecialchars(strtoupper(substr($user["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?></span>
    <?php endif; ?>
</div>
        <div class="profile-identity">
            <div class="profile-name-row">
                <div>
                    <h1><?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></h1>
                    <?php if (!empty($user["rank"])): ?>
                        <span class="profile-rank"><?= htmlspecialchars($user["rank"], ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                </div>
                <a href="edit_profile.php" class="profile-edit-button">Edit Profile</a>
            </div>
            <?php if (!empty($user["bio"])): ?>
                <p class="profile-bio"><?= nl2br(htmlspecialchars($user["bio"], ENT_QUOTES, "UTF-8")) ?></p>
            <?php else: ?>
                <p class="profile-bio profile-empty">No bio yet.</p>
            <?php endif; ?>
            <div class="profile-stats">
                <div class="profile-stat">
                    <strong><?= $post_count ?></strong>
                    <span>Posts</span>
                </div>
                <div class="profile-stat">
                    <strong><?= $follower_count ?></strong>
                    <span>Followers</span>
                </div>
                <div class="profile-stat">
                    <strong><?= $following_count ?></strong>
                    <span>Following</span>
                </div>
            </div>
        </div>
    </section>
    <section class="profile-info-grid">
        <article class="profile-panel">
            <h2>About Me</h2>
            <div class="profile-about-row">
                <span>Username</span>
                <strong><?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <div class="profile-about-row">
                <span>Rank</span>
                <strong><?= htmlspecialchars($user["rank"] ?? "Member", ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <?php if (!empty($user["birthdate"])): ?>
                <div class="profile-about-row">
                    <span>Birthday</span>
                    <strong><?= htmlspecialchars($user["birthdate"], ENT_QUOTES, "UTF-8") ?></strong>
                </div>
            <?php endif; ?>
        </article>
        <article class="profile-panel">
            <h2>Badges</h2>
            <div class="profile-empty-state">
                <span class="profile-empty-icon">✦</span>
                <p>No badges earned yet.</p>
            </div>
        </article>
    </section>
    <section class="profile-posts">
        <div class="profile-section-heading">
            <h2>Posts</h2>
            <span><?= $post_count ?> total</span>
        </div>
        <?php if ($post_count > 0): ?>
            <div class="profile-post-grid">
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <?php
                    $postId = (int) $post["post_id"];
                    $mediaStmt->bind_param("i", $postId);
                    $mediaStmt->execute();
                    $mediaResult = $mediaStmt->get_result();
                    $postMedia = [];
                    while ($media = $mediaResult->fetch_assoc()) {
                        $postMedia[] = $media;
                    }
                    $mediaCount = count($postMedia);
                    $firstMedia = $mediaCount > 0 ? $postMedia[0] : null;
                    ?>
                    <a href="post.php?id=<?= $postId ?>" class="profile-post-card <?= !$firstMedia ? "profile-text-post" : "" ?>" aria-label="View <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>">
                        <?php if ($firstMedia): ?>
                            <?php
                            $mediaFile = basename($firstMedia["file"]);
                            $mediaPath = "uploads/posts/" . rawurlencode($mediaFile);
                            ?>
                            <?php if ($firstMedia["media_type"] === "image"): ?>
                                <img class="profile-post-media" src="<?= $mediaPath ?>" alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>" loading="lazy">
                            <?php elseif ($firstMedia["media_type"] === "video"): ?>
                                <video class="profile-post-media" src="<?= $mediaPath ?>" muted playsinline preload="metadata"></video>
                                <span class="profile-video-icon" aria-hidden="true">▶</span>
                            <?php endif; ?>
                            <?php if ($mediaCount > 1): ?>
                                <span class="profile-media-count"><?= $mediaCount ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="profile-text-content">
                                <span class="profile-text-symbol" aria-hidden="true">✦</span>
                                <?php if (!empty($post["topic"])): ?>
                                    <span class="profile-text-topic">#<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></span>
                                <?php endif; ?>
                                <?php if (!empty($post["description"])): ?>
                                    <p class="profile-text-description"><?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?></p>
                                <?php else: ?>
                                    <p class="profile-text-description"><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="profile-post-overlay">
                            <strong><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="profile-no-posts">
                <span>✦</span>
                <h3>No posts yet</h3>
                <p>Your creations will appear here.</p>
                <a href="upload.php">Create your first post</a>
            </div>
        <?php endif; ?>
    </section>
</section>
<!-- Profile Picture Modal -->
<div
    id="profile-image-modal"
    class="profile-image-modal"
    aria-hidden="true"
>
    <button
        type="button"
        class="profile-image-modal-close"
        aria-label="Close profile picture"
    >
        &times;
    </button>

    <img
        id="profile-modal-image"
        class="profile-modal-image"
        src=""
        alt=""
    >
</div>

<script src="assets/js/profile.js"></script>

<?php
$mediaStmt->close();
$postStmt->close();
$followerStmt->close();
$followingStmt->close();
$stmt->close();
require_once __DIR__ . "/includes/user_footer.php";
?>