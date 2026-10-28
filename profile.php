<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/User.php";
require_once __DIR__ . "/models/Follow.php";

$pageTitle = "Profile | Witchy";
$pageCss = "profile.css";

$user_id = (int) $_SESSION["user_id"];
$profile_id = (int) ($_GET["id"] ?? $user_id);
$is_own_profile = $profile_id === $user_id;

// Get profile user
$userModel = new User($conn);
$user = $profile_id > 0
    ? $userModel->getPublicProfile($profile_id)
    : null;

if (!$user) {
    http_response_code(404);
    exit("User not found.");
}

// Follow information
$followModel = new Follow($conn);

$is_following = false;

if (!$is_own_profile) {
    $is_following = $followModel->isFollowing($user_id, $profile_id);
}

$follower_count = $followModel->countFollowers($profile_id);
$following_count = $followModel->countFollowing($profile_id);

// Get user's posts
$postStmt = $conn->prepare("
    SELECT post_id, title, description, topic, created_at
    FROM post
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$postStmt->execute([$profile_id]);
$posts = $postStmt->fetchAll(PDO::FETCH_ASSOC);
$post_count = count($posts);

// Prepare media query
$mediaStmt = $conn->prepare("
    SELECT file, media_type
    FROM post_media
    WHERE post_id = ?
    ORDER BY sort_order ASC, media_id ASC
");

// Load page
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
            <img
                src="<?= htmlspecialchars(
                    !empty($user["profile_image"])
                        ? $user["profile_image"]
                        : "images/default-profile.jpg",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                alt="<?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>'s profile picture"
                class="profile-avatar-image"
                id="profile-avatar-image"
            >
        </div>

        <div class="profile-identity">

            <div class="profile-name-row">

                <div>
                    <h1><?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></h1>

                    <?php if (!empty($user["rank"])): ?>
                        <span class="profile-rank">
                            <?= htmlspecialchars($user["rank"], ENT_QUOTES, "UTF-8") ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($is_own_profile): ?>

                    <a href="edit_profile.php" class="profile-edit-button">
                        Edit Profile
                    </a>

                <?php else: ?>

                    <form
                        action="follow_user.php"
                        method="POST"
                        class="profile-follow-form"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                        >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= $profile_id ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="<?= $is_following ? "unfollow" : "follow" ?>"
                        >

                        <input
                            type="hidden"
                            name="return_to"
                            value="profile.php?id=<?= $profile_id ?>"
                        >

                        <button
                            type="submit"
                            class="profile-follow-button <?= $is_following ? "active" : "" ?>"
                        >
                            <?= $is_following ? "Following" : "Follow" ?>
                        </button>
                    </form>

                <?php endif; ?>

            </div>

            <?php if (!empty($user["bio"])): ?>

                <p class="profile-bio">
                    <?= nl2br(htmlspecialchars($user["bio"], ENT_QUOTES, "UTF-8")) ?>
                </p>

            <?php else: ?>

                <p class="profile-bio profile-empty">
                    No bio yet.
                </p>

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
                <strong>
                    <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>
                </strong>
            </div>

            <div class="profile-about-row">
                <span>Rank</span>
                <strong>
                    <?= htmlspecialchars($user["rank"] ?? "Member", ENT_QUOTES, "UTF-8") ?>
                </strong>
            </div>

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

                <?php foreach ($posts as $post): ?>

                    <?php
                    $postId = (int) $post["post_id"];

                    $mediaStmt->execute([$postId]);
                    $postMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

                    $mediaCount = count($postMedia);
                    $firstMedia = $mediaCount > 0 ? $postMedia[0] : null;
                    ?>

                    <a
                        href="post.php?id=<?= $postId ?>"
                        class="profile-post-card <?= !$firstMedia ? "profile-text-post" : "" ?>"
                        aria-label="View <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                    >

                        <?php if ($firstMedia): ?>

                            <?php
                            $mediaFile = basename($firstMedia["file"]);
                            $mediaPath = "uploads/posts/" . rawurlencode($mediaFile);
                            ?>

                            <?php if ($firstMedia["media_type"] === "image"): ?>

                                <img
                                    class="profile-post-media"
                                    src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                    alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                    loading="lazy"
                                >

                            <?php elseif ($firstMedia["media_type"] === "video"): ?>

                                <video
                                    class="profile-post-media"
                                    src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                    muted
                                    playsinline
                                    preload="metadata"
                                ></video>

                                <span class="profile-video-icon" aria-hidden="true">
                                    ▶
                                </span>

                            <?php endif; ?>

                            <?php if ($mediaCount > 1): ?>

                                <span class="profile-media-count">
                                    <?= $mediaCount ?>
                                </span>

                            <?php endif; ?>

                        <?php else: ?>

                            <div class="profile-text-content">

                                <span class="profile-text-symbol" aria-hidden="true">
                                    ✦
                                </span>

                                <?php if (!empty($post["topic"])): ?>

                                    <span class="profile-text-topic">
                                        #<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (!empty($post["description"])): ?>

                                    <p class="profile-text-description">
                                        <?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                <?php else: ?>

                                    <p class="profile-text-description">
                                        <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <div class="profile-post-overlay">
                            <strong>
                                <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                            </strong>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="profile-no-posts">

                <span>✦</span>
                <h3>No posts yet</h3>

                <?php if ($is_own_profile): ?>

                    <p>Your creations will appear here.</p>
                    <a href="upload.php">Create your first post</a>

                <?php else: ?>

                    <p>
                        <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>
                        hasn’t posted yet.
                    </p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</section>

<!-- Profile picture modal -->

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
require_once __DIR__ . "/includes/user_footer.php";
?>
