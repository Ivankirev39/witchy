<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Home | Witchy";

$sql = "SELECT * FROM post ORDER BY created_at DESC";
$result = $conn->query($sql);

// Get all posts from the database
// Newest posts will appear first
$sql = "SELECT * FROM post ORDER BY created_at DESC";
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

            <article class="post-card">

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


                <div class="post-image-placeholder">
                    Post image
                </div>


                <div class="post-actions">
                    <span>♡ 0</span>
                    <span>💬 0</span>
                    <span>🔖</span>
                </div>


                <div class="post-body">

                    <strong>
                        <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                    </strong>

                    <p>
                        <?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                </div>

            </article>

        <?php endwhile; ?>

    </section>

</section>


<?php
require_once __DIR__ . "/includes/user_footer.php";
?>