<?php
require_once __DIR__ . "/includes/auth.php";

$pageTitle = "Post Not Found | Witchy";

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="post-not-found-page">
    <div class="post-not-found-card">
        <div class="post-not-found-icon">
            ☾
        </div>

        <h1>Post not found</h1>

        <p>
            This post may have been deleted, removed, or no longer exists.
        </p>

        <a href="feed.php" class="post-not-found-button">
            Back to feed
        </a>
    </div>
</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
```