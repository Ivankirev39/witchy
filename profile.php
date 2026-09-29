<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Profile | Witchy";
$user_id = $_SESSION["user_id"];


// ========================================
// GET LOGGED-IN USER
// ========================================

$stmt = $conn->prepare("
    SELECT username, email, birthdate, rank, bio, profile_image
    FROM users
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}


// ========================================
// GET USER'S POSTS
// ========================================

$postStmt = $conn->prepare("
    SELECT post_id, image, title, created_at
    FROM post
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$postStmt->bind_param("i", $user_id);
$postStmt->execute();

$posts = $postStmt->get_result();

$post_count = $posts->num_rows;


// ========================================
// FOLLOWER COUNT
// ========================================

$followerStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM follow
    WHERE following_id = ?
");

$followerStmt->bind_param("i", $user_id);
$followerStmt->execute();

$follower_count = $followerStmt->get_result()->fetch_assoc()["total"];


// ========================================
// FOLLOWING COUNT
// ========================================

$followingStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM follow
    WHERE follower_id = ?
");

$followingStmt->bind_param("i", $user_id);
$followingStmt->execute();

$following_count = $followingStmt->get_result()->fetch_assoc()["total"];


// ========================================
// PAGE HEADER / SIDEBAR
// ========================================

$pageCss = "profile.css";
require_once __DIR__ . "/includes/user_header.php";

?>


<section class="profile-page">

    <!-- PROFILE COVER -->
    <div class="profile-cover">
        <span>WITCHY</span>
    </div>


    <!-- PROFILE HEADER -->
    <section class="profile-header">

        <div class="profile-avatar">

            <?php if (!empty($user["profile_image"])): ?>

                <img
                    src="<?= htmlspecialchars(
                        $user["profile_image"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    alt="<?= htmlspecialchars(
                        $user["username"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>'s profile picture"
                >

            <?php else: ?>

                <span>
                    <?= htmlspecialchars(
                        strtoupper(substr($user["username"], 0, 1)),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>

            <?php endif; ?>

        </div>

        <div class="profile-identity">

            <div class="profile-name-row">

                <div>
                    <h1>
                        <?= htmlspecialchars(
                            $user["username"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h1>

                    <?php if (!empty($user["rank"])): ?>
                        <span class="profile-rank">
                            <?= htmlspecialchars(
                                $user["rank"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a href="edit_profile.php" class="profile-edit-button">
                    Edit Profile
                </a>

            </div>


            <?php if (!empty($user["bio"])): ?>

                <p class="profile-bio">
                    <?= nl2br(
                        htmlspecialchars(
                            $user["bio"],
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    ) ?>
                </p>

            <?php else: ?>

                <p class="profile-bio profile-empty">
                    No bio yet.
                </p>

            <?php endif; ?>


            <!-- PROFILE STATS -->
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


    <!-- PROFILE INFORMATION -->
    <section class="profile-info-grid">

        <!-- ABOUT -->
        <article class="profile-panel">

            <h2>About Me</h2>

            <div class="profile-about-row">
                <span>Username</span>
                <strong>
                    <?= htmlspecialchars(
                        $user["username"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>
            </div>

            <div class="profile-about-row">
                <span>Rank</span>
                <strong>
                    <?= htmlspecialchars(
                        $user["rank"] ?? "Member",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>
            </div>

            <?php if (!empty($user["birthdate"])): ?>

                <div class="profile-about-row">
                    <span>Birthday</span>
                    <strong>
                        <?= htmlspecialchars(
                            $user["birthdate"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>
                </div>

            <?php endif; ?>

        </article>


        <!-- BADGES -->
        <article class="profile-panel">

            <h2>Badges</h2>

            <div class="profile-empty-state">
                <span class="profile-empty-icon">✦</span>
                <p>No badges earned yet.</p>
            </div>

        </article>

    </section>


    <!-- POSTS -->
    <section class="profile-posts">

        <div class="profile-section-heading">
            <h2>Posts</h2>
            <span><?= $post_count ?> total</span>
        </div>


        <?php if ($post_count > 0): ?>

            <div class="profile-post-grid">

                <?php while ($post = $posts->fetch_assoc()): ?>

                    <article class="profile-post-card">

                        <?php if (!empty($post["image"])): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $post["image"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $post["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                        <?php else: ?>

                            <div class="profile-post-placeholder">
                                ✦
                            </div>

                        <?php endif; ?>

                        <div class="profile-post-overlay">
                            <strong>
                                <?= htmlspecialchars(
                                    $post["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="profile-no-posts">
                <span>✦</span>
                <h3>No posts yet</h3>
                <p>Your creations will appear here.</p>

                <a href="upload.php">
                    Create your first post
                </a>
            </div>

        <?php endif; ?>

    </section>

</section>


<?php

require_once __DIR__ . "/includes/user_footer.php";

?>