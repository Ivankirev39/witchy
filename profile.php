<?php
require_once __DIR__ . "/config/bootstrap.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/User.php";
require_once __DIR__ . "/models/Follow.php";
require_once __DIR__ . "/models/Report.php";

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

// Get user's posts and profile pin status
$postStmt = $conn->prepare("
    SELECT
        p.post_id,
        p.title,
        p.description,
        p.topic,
        p.created_at,
        CASE
            WHEN pp.post_id IS NOT NULL THEN 1
            ELSE 0
        END AS is_profile_pinned
    FROM post p
    LEFT JOIN profile_pinned_post pp
        ON pp.post_id = p.post_id
        AND pp.user_id = ?
    WHERE p.user_id = ?
    ORDER BY
        is_profile_pinned DESC,
        pp.pinned_at DESC,
        p.created_at DESC
");

$postStmt->execute([$profile_id, $profile_id]);
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

    <?php
    $isOwnProfile = ($profile_id === (int) $user_id);

    if (!$isOwnProfile):
        $returnUrl = $_GET["return"] ?? "";
        $backUrl = BASE_URL . "explore.php";

        $parsedUrl = parse_url($returnUrl);

        if (
            !empty($parsedUrl["path"]) &&
            str_starts_with($parsedUrl["path"], "/witchy/") &&
            !isset($parsedUrl["host"])
        ) {
            $backUrl = $returnUrl;
        }
    ?>

        <a
            href="<?= htmlspecialchars($backUrl, ENT_QUOTES, "UTF-8") ?>"
            class="profile-back-button">
            &larr; Back to results
        </a>

    <?php endif; ?>

    <div class="profile-cover">
        <?php if (!empty($user["cover_image"])): ?>
            <img
                src="<?= htmlspecialchars(
                            BASE_URL . ltrim($user["cover_image"], "/"),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                alt="">
        <?php else: ?>
            <span>WITCHY</span>
        <?php endif; ?>
    </div>

    <section class="profile-header">

        <div class="profile-avatar">
            <img
                src="<?= htmlspecialchars(
                            BASE_URL . ltrim(
                                !empty($user["profile_image"])
                                    ? $user["profile_image"]
                                    : "images/default-profile.jpg",
                                "/"
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                alt="<?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>'s profile picture"
                class="profile-avatar-image"
                id="profile-avatar-image">
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

                    <div class="profile-owner-actions">
                        <a href="<?= BASE_URL ?>edit_profile.php" class="profile-edit-button">
                            Edit Profile
                        </a>

                        <a href="<?= BASE_URL ?>settings.php" class="profile-settings-button">
                            Account Settings
                        </a>
                    </div>

                <?php else: ?>
                    <div class="profile-user-actions">
                    <form
                        action="<?= BASE_URL ?>follow_user.php"
                        method="POST"
                        class="profile-follow-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= $profile_id ?>">

                        <input
                            type="hidden"
                            name="action"
                            value="<?= $is_following ? "unfollow" : "follow" ?>">

                        <input
                            type="hidden"
                            name="return_to"
                            value="profile.php?id=<?= $profile_id ?>">

                        <button
                            type="submit"
                            class="profile-follow-button <?= $is_following ? "active" : "" ?>">
                            <?= $is_following ? "Following" : "Follow" ?>
                        </button>
            
                    </form>
                    <button
                        type="button"
                        class="report-button"
                        data-report-type="user"
                        data-target-id="<?= $profile_id ?>">
                        Report profile
                    </button>
                        </div>
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

            <div class="profile-post-grid" id="profile-post-grid">

                <?php foreach ($posts as $post): ?>

                    <?php
                    $postId = (int) $post["post_id"];

                    $mediaStmt->execute([$postId]);
                    $postMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

                    $mediaCount = count($postMedia);
                    $firstMedia = $mediaCount > 0 ? $postMedia[0] : null;
                    ?>

                    <div class="profile-post-item" id="post-<?= $postId ?>">

                        <?php if ($is_own_profile): ?>

                            <form
                                action="<?= BASE_URL ?>profile_pin.php"
                                method="POST"
                                class="profile-pin-form">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                                <input
                                    type="hidden"
                                    name="post_id"
                                    value="<?= $postId ?>">

                                <button
                                    type="submit"
                                    class="profile-pin-button <?= !empty($post["is_profile_pinned"]) ? "is-pinned" : "" ?>"
                                    aria-label="<?= !empty($post["is_profile_pinned"]) ? "Unpin post" : "Pin post to profile" ?>"
                                    title="<?= !empty($post["is_profile_pinned"]) ? "Unpin post" : "Pin to profile" ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 3h6l-1 6 3 3v2H7v-2l3-3-1-6Z" />
                                        <path d="M12 14v7" />
                                    </svg>
                                </button>
                            </form>

                        <?php endif; ?>

                        <a
                            href="<?= BASE_URL ?>post.php?id=<?= $postId ?>"
                            class="profile-post-card <?= !$firstMedia ? "profile-text-post" : "" ?>"
                            aria-label="View <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>">

                            <?php if ($firstMedia): ?>

                                <?php
                                $mediaFile = basename($firstMedia["file"]);
                                $mediaPath = BASE_URL . "uploads/posts/" . rawurlencode($mediaFile);
                                ?>

                                <?php if ($firstMedia["media_type"] === "image"): ?>

                                    <img
                                        class="profile-post-media"
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                        loading="lazy">

                                <?php elseif ($firstMedia["media_type"] === "video"): ?>

                                    <video
                                        class="profile-post-media"
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        muted
                                        playsinline
                                        preload="metadata"></video>

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

                                    <p class="profile-text-description">
                                        <?= htmlspecialchars(
                                            !empty($post["description"])
                                                ? $post["description"]
                                                : $post["title"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </p>

                                </div>

                            <?php endif; ?>

                            <div class="profile-post-overlay">
                                <strong>
                                    <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                                </strong>
                            </div>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="profile-no-posts">

                <span>✦</span>
                <h3>No posts yet</h3>

                <?php if ($is_own_profile): ?>

                    <p>Your creations will appear here.</p>
                    <a href="<?= BASE_URL ?>upload.php">Create your first post</a>

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

<!-- REPORT MODAL -->
<div
    id="report-modal"
    class="report-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="report-modal-title"
    hidden
>
    <div class="report-modal-content">

        <button
            type="button"
            class="report-modal-close"
            aria-label="Close report form"
        >
            &times;
        </button>

        <h2 id="report-modal-title">Report user</h2>

        <p>Why are you reporting this content?</p>

        <form id="report-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
            >

            <input
                type="hidden"
                name="report_type"
                id="report-type"
                value="user"
            >

            <input
                type="hidden"
                name="target_id"
                id="report-target-id"
            >

            <label for="report-reason">Reason</label>

            <select name="reason" id="report-reason" required>
                <option value="">Select a reason</option>
                <option value="Harassment or bullying">Harassment or bullying</option>
                <option value="Spam">Spam</option>
                <option value="Hate speech">Hate speech</option>
                <option value="Inappropriate content">Inappropriate content</option>
                <option value="Misinformation">Misinformation</option>
                <option value="Other">Other</option>
            </select>

            <label for="report-details">
                Additional details (optional)
            </label>

            <textarea
                name="details"
                id="report-details"
                maxlength="1000"
                placeholder="Tell us more about the issue..."
            ></textarea>

            <div class="report-modal-actions">
                <button type="button" class="report-cancel-button">
                    Cancel
                </button>

                <button type="submit" class="report-submit-button">
                    Submit report
                </button>
            </div>

            <p
                id="report-message"
                class="report-message"
                role="status"
                aria-live="polite"
                hidden
            ></p>

        </form>
    </div>
</div>

<!-- Profile picture modal -->
<div
    id="profile-image-modal"
    class="profile-image-modal"
    aria-hidden="true">
    <button
        type="button"
        class="profile-image-modal-close"
        aria-label="Close profile picture">
        &times;
    </button>

    <img
        id="profile-modal-image"
        class="profile-modal-image"
        src=""
        alt="">
</div>

<script src="<?= BASE_URL ?>assets/js/profile.js?v=2"></script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>