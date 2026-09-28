<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
$pageTitle = "Home | Witchy";
$user_id = $_SESSION["user_id"];

$sql = "
    SELECT 
        post.*,

        (SELECT COUNT(*)
         FROM post_like
         WHERE post_like.post_id = post.post_id
        ) AS like_count,

        (SELECT COUNT(*)
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
    ORDER BY created_at DESC
";
$result = $conn->query($sql);
require_once __DIR__ . "/includes/user_header.php";
?>
<section class="feed-page">
    <header class="feed-header">
        <div>
            <h1>Home</h1>
            <p>
                Welcome back,
                <?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?>.
            </p>
        </div>
    </header>
    <div class="feed-tabs">
        <button class="feed-tab active">For you</button>
        <button class="feed-tab">Following</button>
    </div>
    <section class="feed-content">
        <?php while ($post = $result->fetch_assoc()): ?>
            <article class="post-card" id="post-<?= $post["post_id"] ?>">
                <div class="post-author">
                    <div class="post-avatar"></div>
                    <div>
                        <strong>
                            <?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?>
                        </strong>
                        <p>
                            <?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($post["image"])): ?>
                 <img
                   class="post-image"
                   src="uploads/posts/<?= rawurlencode(basename($post["image"])) ?>"
                   alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                   loading="lazy"
                  >
                 <?php endif; ?>
                <div class="post-body">
                    <strong>
                        <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                    </strong>
                    <p>
                        <?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?>
                    </p>
                </div>
                <div class="post-actions">
                    <form action="like_post.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post["post_id"] ?>">
                        <button type="submit" class="post-action-button like-button <?= $post["user_liked"] ? "active" : "" ?>">
                            <span class="like-heart">
                                <?= $post["user_liked"] ? "♥" : "♡" ?>
                            </span>
                            <?= $post["like_count"] ?>
                        </button>
                    </form>
                    <a href="?comments=<?= (int) $post["post_id"] ?>#post-<?= (int) $post["post_id"] ?>" class="post-action-button">
                        💬 <?= $post["comment_count"] ?>
                    </a>
                    <form action="save_post.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post["post_id"] ?>">
                        <button type="submit" class="post-action-button save-button <?= $post["user_saved"] ? "active" : "" ?>">
                            🔖
                        </button>
                    </form>
                </div>
                <?php if (
                    isset($_GET["comments"]) &&
                    (int) $_GET["comments"] === (int) $post["post_id"]
                ): ?>
                    <div class="post-comments">
                        <?php
                        $commentStmt = $conn->prepare("
                            SELECT comment.comment, comment.created_at, users.username
                            FROM comment
                            JOIN users ON comment.user_id = users.user_id
                            WHERE comment.post_id = ?
                            ORDER BY comment.created_at ASC
                        ");
                        $commentStmt->bind_param("i", $post["post_id"]);
                        $commentStmt->execute();
                        $comments = $commentStmt->get_result();
                        ?>
                        <?php if ($comments->num_rows > 0): ?>
                            <?php while ($comment = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <strong>
                                        <?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?>
                                    </strong>
                                    <p>
                                        <?= htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8") ?>
                                    </p>
                                    <small>
                                        <?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?>
                                    </small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <form action="comment_post.php" method="POST" class="comment-form">
                    <input type="hidden" name="post_id" value="<?= $post["post_id"] ?>">
                    <input type="text" name="comment" placeholder="Write a comment..." maxlength="500" required>
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