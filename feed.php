<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Home | Witchy";
$user_id = $_SESSION["user_id"];
// ========================================
// GET POSTS
// ========================================

$sql = "
    SELECT 
        post.*,

        users.username AS author_username,
        users.profile_image AS author_profile_image,

        (
            SELECT COUNT(*)
            FROM post_like
            WHERE post_like.post_id = post.post_id
        ) AS like_count,

        (
            SELECT COUNT(*)
            FROM comment
            WHERE comment.post_id = post.post_id
        ) AS comment_count,

        EXISTS(
            SELECT 1
            FROM post_like
            WHERE post_like.post_id = post.post_id
            AND post_like.user_id = $user_id
        ) AS user_liked,

        EXISTS(
            SELECT 1
            FROM save
            WHERE save.post_id = post.post_id
            AND save.user_id = $user_id
        ) AS user_saved

    FROM post
    JOIN users
        ON users.user_id = post.user_id
    ORDER BY post.created_at DESC
";

$result = $conn->query($sql);
// ========================================
// LOAD HEADER
// ========================================
require_once __DIR__ . "/includes/user_header.php";
?>

<section class="feed-page">
    <!-- ========================================
         FEED HEADER
         ======================================== -->
    <header class="feed-header">
        <div>
            <h1>Home</h1>
            <p>
                Welcome back,
                <?= htmlspecialchars(
                    $_SESSION["username"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>.
            </p>
        </div>
    </header>
    <!-- ========================================
         FEED TABS
         ======================================== -->
    <div class="feed-tabs">
        <button class="feed-tab active">
            For you
        </button>
        <button class="feed-tab">
            Following
        </button>
    </div>
    <!-- ========================================
         FEED
         ======================================== -->
    <section class="feed-content">
        <?php while ($post = $result->fetch_assoc()): ?>
            <article
                class="post-card"
                id="post-<?= (int) $post["post_id"] ?>"
            >
                <!-- ========================================
                     POST AUTHOR
                     ======================================== -->
                <div class="post-author">
                    <!-- AUTHOR AVATAR -->
                    <?php if (!empty($post["author_profile_image"])): ?>
                        <img
                            class="post-avatar"
                            src="<?= htmlspecialchars(
                                $post["author_profile_image"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            alt=""
                        >
                    <?php else: ?>
                        <div class="post-avatar">
                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $post["author_username"],
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>
                    <?php endif; ?>
                    <!-- AUTHOR INFORMATION -->
                    <div>
                        <strong>
                            <?= htmlspecialchars(
                                $post["author_username"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                        <p>
                            <?= htmlspecialchars(
                                $post["created_at"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>
                    </div>
                </div>
                <!-- ========================================
                     POST IMAGE
                     ======================================== -->
                <?php if (!empty($post["image"])): ?>
                    <a
                        href="post.php?id=<?= (int) $post["post_id"] ?>"
                        class="post-image-link"
                    >
                        <img
                            class="post-image"
                            src="uploads/posts/<?= rawurlencode(
                                basename($post["image"])
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $post["title"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            loading="lazy"
                        >
                    </a>
                <?php endif; ?>
                <!-- ========================================
                     POST BODY
                     ======================================== -->
                <div class="post-body">
                    <!-- CLICKABLE TITLE -->
                    <strong>
                        <a
                            href="post.php?id=<?= (int) $post["post_id"] ?>"
                            class="post-title-link"
                        >
                            <?= htmlspecialchars(
                                $post["title"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </a>
                    </strong>
                    <!-- DESCRIPTION -->
                    <?php if (!empty($post["description"])): ?>
                        <p>
                            <?= htmlspecialchars(
                                $post["description"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>
                    <?php endif; ?>
                    <!-- TOPIC -->
                    <?php if (!empty($post["topic"])): ?>
                        <span class="post-topic">
                            #<?= htmlspecialchars(
                                $post["topic"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <!-- ========================================
                     POST ACTIONS
                     ======================================== -->
                <div class="post-actions">
                    <!-- LIKE -->
                    <form
                        action="like_post.php"
                        method="POST"
                    >
                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= (int) $post["post_id"] ?>"
                        >
                        <button
                            type="submit"
                            class="post-action-button like-button <?= $post["user_liked"] ? "active" : "" ?>"
                        >
                            <span class="like-heart">
                                <?= $post["user_liked"] ? "♥" : "♡" ?>
                            </span>
                            <?= (int) $post["like_count"] ?>
                        </button>
                    </form>
                    <!-- COMMENTS -->
                    <a
                        href="?comments=<?= (int) $post["post_id"] ?>#post-<?= (int) $post["post_id"] ?>"
                        class="post-action-button"
                    >
                        💬 <?= (int) $post["comment_count"] ?>
                    </a>
                    <!-- SAVE -->
                    <form
                        action="save_post.php"
                        method="POST"
                    >
                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= (int) $post["post_id"] ?>"
                        >
                        <button
                            type="submit"
                            class="post-action-button save-button <?= $post["user_saved"] ? "active" : "" ?>"
                        >
                            🔖
                        </button>
                    </form>
                </div>
                <!-- ========================================
                     EXPANDED COMMENTS
                     ======================================== -->
                <?php if (
                    isset($_GET["comments"]) &&
                    (int) $_GET["comments"] === (int) $post["post_id"]
                ): ?>
                    <div class="post-comments">
                        <?php
                        $commentStmt = $conn->prepare("
                            SELECT
                                comment.comment,
                                comment.created_at,
                                users.username
                            FROM comment
                            JOIN users
                                ON comment.user_id = users.user_id
                            WHERE comment.post_id = ?
                            ORDER BY comment.created_at ASC
                        ");
                        $commentStmt->bind_param(
                            "i",
                            $post["post_id"]
                        );
                        $commentStmt->execute();
                        $comments = $commentStmt->get_result();
                        ?>
                        <?php if ($comments->num_rows > 0): ?>
                            <?php while ($comment = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <strong>
                                        <?= htmlspecialchars(
                                            $comment["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>
                                    <p>
                                        <?= htmlspecialchars(
                                            $comment["comment"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </p>
                                    <small>
                                        <?= htmlspecialchars(
                                            $comment["created_at"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>
                                No comments yet.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <!-- ========================================
                     ADD COMMENT
                     ======================================== -->
                <form
                    action="comment_post.php"
                    method="POST"
                    class="comment-form"
                >
                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= (int) $post["post_id"] ?>"
                    >
                    <input
                        type="text"
                        name="comment"
                        placeholder="Write a comment..."
                        maxlength="500"
                        required
                    >
                    <button type="submit">
                        Post
                    </button>
                </form>
            </article>
        <?php endwhile; ?>
    </section>
</section>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>