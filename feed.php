<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Home | Witchy";

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

        <article class="post-card">

            <div class="post-author">
                <div class="post-avatar"></div>

                <div>
                    <strong><?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?></strong>
                    <p>Just now</p>
                </div>
            </div>

            <div class="post-image-placeholder">
                Post image
            </div>

            <div class="post-actions">
                <span>♡ 0</span>
                <span>💬 0</span>
                <span>🔖</span>
            </div>

            <div class="post-body">
                <strong>My first Witchy post</strong>
                <p>This will later come from the database.</p>
            </div>

        </article>

    </section>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>