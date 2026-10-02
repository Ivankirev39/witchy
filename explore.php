<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Explore | Witchy";
$pageCss = "explore.css";

$posts = [];

$sql = "
    SELECT
        p.post_id,
        p.user_id,
        p.title,
        p.description,
        p.image,
        p.topic,
        p.created_at,
        u.username
    FROM post p
    INNER JOIN users u
        ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="explore-page">

    <header class="explore-header">
        <h1>Explore</h1>
        <p>
            Discover posts from the Witchy community.
        </p>
    </header>

    <section class="explore-content">

    <div class="explore-grid">

        <?php if (empty($posts)): ?>

            <p class="explore-empty">
                No posts to explore yet.
            </p>

        <?php else: ?>

            <?php foreach ($posts as $post): ?>
                <article class="explore-card">
                <div class="explore-card-image">

        <?php if (!empty($post['image'])): ?>
            <img
                src="uploads/posts/<?php echo rawurlencode(basename($post['image'])); ?>"
                alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>"
                loading="lazy"
            >
        <?php else: ?>
            <div class="explore-card-placeholder">
                No image
            </div>
        <?php endif; ?>
        
    </div>

                    <div class="explore-card-body">

                        <p class="explore-card-author">
                            @<?php echo htmlspecialchars($post['username']); ?>
                        </p>
                        <h2 class="explore-card-title">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h2>

                        <?php if (!empty($post['description'])): ?>
                            <p class="explore-card-description">
                                <?php
                                echo htmlspecialchars(
                                    mb_strimwidth(
                                        $post['description'],
                                        0,
                                        140,
                                        "..."
                                    )
                                );
                                ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($post['topic'])): ?>
                            <p class="explore-card-topic">
                                #<?php echo htmlspecialchars($post['topic']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>