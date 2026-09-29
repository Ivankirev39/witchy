<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Post | Witchy";
$pageCss = "post.css";

$user_id = $_SESSION["user_id"];


// ========================================
// GET POST ID
// ========================================

$post_id = (int) ($_GET["id"] ?? 0);

if ($post_id <= 0) {
    header("Location: feed.php");
    exit;
}
// ========================================
// GET POST + AUTHOR + COUNTS
// ========================================

$stmt = $conn->prepare("
    SELECT
        post.*,

        users.username,
        users.profile_image,

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

        EXISTS (
            SELECT 1
            FROM post_like
            WHERE post_like.post_id = post.post_id
            AND post_like.user_id = ?
        ) AS user_liked,

        EXISTS (
            SELECT 1
            FROM save
            WHERE save.post_id = post.post_id
            AND save.user_id = ?
        ) AS user_saved,

        EXISTS (
            SELECT 1
            FROM follow
            WHERE follow.follower_id = ?
            AND follow.following_id = post.user_id
        ) AS user_follows

    FROM post

    INNER JOIN users
        ON users.user_id = post.user_id

    WHERE post.post_id = ?
");

$stmt->bind_param(
    "iiii",
    $user_id,
    $user_id,
    $user_id,
    $post_id
);

$stmt->execute();

$post = $stmt->get_result()->fetch_assoc();


// ========================================
// POST DOES NOT EXIST
// ========================================

if (!$post) {
    http_response_code(404);
    die("Post not found.");
}


// ========================================
// GET COMMENTS + COMMENT AUTHORS
// ========================================

$commentStmt = $conn->prepare("
    SELECT
        comment.comment_id,
        comment.comment,
        comment.created_at,

        users.user_id,
        users.username,
        users.profile_image

    FROM comment

    INNER JOIN users
        ON users.user_id = comment.user_id

    WHERE comment.post_id = ?

    ORDER BY comment.created_at ASC
");

$commentStmt->bind_param("i", $post_id);
$commentStmt->execute();

$comments = $commentStmt->get_result();


// ========================================
// LOAD HEADER
// ========================================

require_once __DIR__ . "/includes/user_header.php";

?>


<section class="post-detail-page">


    <!-- BACK -->
    <a href="feed.php" class="post-detail-back">
        ← Back
    </a>


    <div class="post-detail-layout">


        <!-- ========================================
             LEFT SIDE - IMAGE
             ======================================== -->

        <!-- ========================================
     LEFT SIDE - IMAGE
     ======================================== -->

    <div class="post-detail-media">

        <?php if (!empty($post["image"])): ?>

            <img
                src="uploads/posts/<?= rawurlencode(basename($post["image"])) ?>"
                alt="<?= htmlspecialchars(
                    $post["title"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        <?php else: ?>

            <div class="post-detail-no-image">
                No image
            </div>

        <?php endif; ?>

    </div>
        <!-- ========================================
             RIGHT SIDE
             ======================================== -->

        <article class="post-detail-content">
            <!-- AUTHOR -->
            <header class="post-detail-author-row">
                <div class="post-detail-author">
                    <!-- PROFILE IMAGE / INITIAL -->

                    <?php if (!empty($post["profile_image"])): ?>

                        <img
                            class="post-detail-avatar"
                            src="<?= htmlspecialchars(
                                $post["profile_image"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            alt=""
                        >

                    <?php else: ?>

                        <div class="post-detail-avatar post-detail-avatar-fallback">

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $post["username"],
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <div>

                        <strong>
                            <?= htmlspecialchars(
                                $post["username"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                        <span class="post-detail-date">

                            <?= htmlspecialchars(
                                date(
                                    "j M Y",
                                    strtotime($post["created_at"])
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    </div>

                </div>



                <!-- FOLLOW BUTTON -->

                <?php if ((int) $post["user_id"] !== (int) $user_id): ?>

                    <form
                        action="follow_user.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $post["user_id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="return_post"
                            value="<?= (int) $post_id ?>"
                        >

                        <button
                            type="submit"
                            class="post-detail-follow <?= $post["user_follows"] ? "active" : "" ?>"
                        >
                            <?= $post["user_follows"] ? "Following" : "Follow" ?>
                        </button>
                    </form>
                <?php endif; ?>
            </header>

            <!-- ========================================
                 POST INFORMATION
                 ======================================== -->

            <div class="post-detail-info">

                <h1>
                    <?= htmlspecialchars(
                        $post["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </h1>


                <?php if (!empty($post["description"])): ?>
                    <p class="post-detail-description">
                        <?= nl2br(
                            htmlspecialchars(
                                $post["description"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        ) ?>

                    </p>
                <?php endif; ?>
                <!-- TOPIC -->

                <?php if (!empty($post["topic"])): ?>
                    <div class="post-detail-topics">
                        <span>
                            #<?= htmlspecialchars(
                                $post["topic"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>

                <?php endif; ?>



                <!-- SOURCES -->

                <?php if (!empty($post["sources"])): ?>

                    <div class="post-detail-sources">

                        <strong>Sources</strong>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $post["sources"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>

            </div>



            <!-- ========================================
                 POST ACTIONS
                 ======================================== -->

            <div class="post-detail-actions">


                <!-- LIKE -->

                <form
                    action="like_post.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= (int) $post_id ?>"
                    >

                    <button
                        type="submit"
                        class="post-detail-action <?= $post["user_liked"] ? "active" : "" ?>"
                    >
                        ♡

                        <span>
                            <?= (int) $post["like_count"] ?>
                        </span>
                    </button>

                </form>



                <!-- COMMENTS -->

                <div class="post-detail-action">

                    ◯

                    <span>
                        <?= (int) $post["comment_count"] ?>
                    </span>

                </div>



                <!-- SAVE -->

                <form
                    action="save_post.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= (int) $post_id ?>"
                    >

                    <button
                        type="submit"
                        class="post-detail-action <?= $post["user_saved"] ? "active" : "" ?>"
                    >
                        🔖
                    </button>

                </form>

            </div>



            <!-- ========================================
                 COMMENTS
                 ======================================== -->

            <section class="post-detail-comments">

                <h2>
                    Comments
                </h2>


                <div class="post-detail-comment-list">


                    <?php if ($comments->num_rows > 0): ?>


                        <?php while ($comment = $comments->fetch_assoc()): ?>

                            <article class="post-detail-comment">


                                <!-- COMMENT AVATAR -->

                                <?php if (!empty($comment["profile_image"])): ?>

                                    <img
                                        class="comment-avatar"
                                        src="<?= htmlspecialchars(
                                            $comment["profile_image"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt=""
                                    >

                                <?php else: ?>

                                    <div class="comment-avatar comment-avatar-fallback">

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                substr(
                                                    $comment["username"],
                                                    0,
                                                    1
                                                )
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </div>

                                <?php endif; ?>



                                <!-- COMMENT CONTENT -->

                                <div class="comment-content">

                                    <div class="comment-meta">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $comment["username"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                date(
                                                    "j M",
                                                    strtotime(
                                                        $comment["created_at"]
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                    </div>


                                    <p>
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $comment["comment"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )
                                        ) ?>
                                    </p>

                                </div>

                            </article>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <p class="post-detail-no-comments">
                            No comments yet. Be the first to comment.
                        </p>
                    <?php endif; ?>
                </div>
                <!-- ========================================
                     ADD COMMENT
                     ======================================== -->
                <form
                    action="comment_post.php"
                    method="POST"
                    class="post-detail-comment-form"
                >
                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= (int) $post_id ?>"
                    >
                    <input
                        type="text"
                        name="comment"
                        placeholder="Add a comment..."
                        maxlength="1000"
                        required
                    >
                    <button
                        type="submit"
                        aria-label="Post comment"
                    >
                        ➤
                    </button>
                </form>
            </section>
        </article>
    </div>
</section>


<?php
require_once __DIR__ . "/includes/user_footer.php";
?>