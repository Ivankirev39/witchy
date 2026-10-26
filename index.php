<?php
require_once __DIR__ . "/includes/session.php";

if (isset($_SESSION["user_id"]) && ($_GET["preview"] ?? "") !== "1") {
    header("Location: feed.php");
    exit;
}

$pageTitle = "Witchy";
$pageCss = "index.css";

require_once __DIR__ . "/models/DBConnector.php";

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$recentPosts = [];

$sql = "
    SELECT
        p.post_id,
        p.title,
        p.description,
        p.topic,
        p.created_at,
        u.username,
        (
            SELECT pm.file
            FROM post_media pm
            WHERE pm.post_id = p.post_id
            ORDER BY pm.sort_order ASC, pm.media_id ASC
            LIMIT 1
        ) AS media_file,
        (
            SELECT pm.media_type
            FROM post_media pm
            WHERE pm.post_id = p.post_id
            ORDER BY pm.sort_order ASC, pm.media_id ASC
            LIMIT 1
        ) AS media_type,
        (
            SELECT COUNT(*)
            FROM post_media pm
            WHERE pm.post_id = p.post_id
        ) AS media_count
    FROM post p
    INNER JOIN users u ON u.user_id = p.user_id
    ORDER BY p.created_at DESC, p.post_id DESC
    LIMIT 6
";

$stmt = $pdo->query($sql);
$recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/includes/public-header.php";
?>

<main>
    <!-- HERO -->
    <section class="landing-hero" id="about">
        <div class="landing-hero-content">
            <div class="landing-symbol" aria-hidden="true">✦</div>

            <h1>Explore what draws you in</h1>

            <p class="landing-tagline">
                Share. Discover. Learn. Belong.
            </p>

            <p class="landing-intro">
                Witchy is a visual community for exploring
                witchcraft, spirituality, folklore and related
                practices. Discover ideas, learn from different
                perspectives, and connect with people who share
                your interests.
            </p>

            <div class="landing-hero-actions">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <a
                        href="#topics"
                        class="button button-primary button-large"
                    >
                        Explore Witchy
                    </a>
                <?php else: ?>
                    <a
                        href="register.php"
                        class="button button-primary button-large"
                    >
                        Join Witchy
                    </a>

                    <a
                        href="#topics"
                        class="button button-secondary button-large"
                    >
                        Explore
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="landing-hero-visual">
            <img
                src="images/witchy-hero.png"
                alt="Witchcraft altar with herbs, tarot cards, crystals and candles"
                class="hero-image"
            >
        </div>
    </section>

    <!-- FOUR CORE PARTS -->
    <section
        class="core-actions"
        aria-label="What you can do on Witchy"
    >
        <!-- SHARE -->
        <article class="core-action">
            <img
                src="images/share.png"
                alt=""
                class="core-action-image"
            >

            <div class="core-action-content">
                <div class="core-action-icon" aria-hidden="true">◇</div>
                <h2>Share</h2>
                <p>Ideas &amp; knowledge</p>
            </div>
        </article>

        <!-- CONNECT -->
        <article class="core-action">
            <img
                src="images/connect.png"
                alt=""
                class="core-action-image"
            >

            <div class="core-action-content">
                <div class="core-action-icon" aria-hidden="true">◎</div>
                <h2>Connect</h2>
                <p>With the community</p>
            </div>
        </article>

        <!-- SAVE -->
        <article class="core-action">
            <img
                src="images/save.png"
                alt=""
                class="core-action-image"
            >

            <div class="core-action-content">
                <div class="core-action-icon" aria-hidden="true">♡</div>
                <h2>Save</h2>
                <p>Inspiration for later</p>
            </div>
        </article>

        <!-- EXPLORE -->
        <article class="core-action">
            <img
                src="images/explore.png"
                alt=""
                class="core-action-image"
            >

            <div class="core-action-content">
                <div class="core-action-icon" aria-hidden="true">✦</div>
                <h2>Explore</h2>
                <p>Topics that interest you</p>
            </div>
        </article>
    </section>

    <!-- TOPICS -->
    <section
        class="landing-section topics-section"
        id="topics"
    >
        <div class="section-heading">
            <div>
                <p class="section-eyebrow">Discover</p>

                <h2>What can you explore?</h2>

                <p class="section-description">
                    Follow your curiosity through topics,
                    practices and perspectives from across
                    the Witchy community.
                </p>
            </div>
        </div>

        <div class="topic-grid">
            <a href="public-explore.php?topic=Spells" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">✦</div>
                <span>Spells</span>
            </a>

            <a href="public-explore.php?topic=Altars" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">☾</div>
                <span>Altars</span>
            </a>

            <a href="public-explore.php?topic=Tarot" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">◇</div>
                <span>Tarot</span>
            </a>

            <a href="public-explore.php?topic=Herbs" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">✿</div>
                <span>Herbs</span>
            </a>

            <a href="public-explore.php?topic=Crystals" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">✧</div>
                <span>Crystals</span>
            </a>

            <a href="public-explore.php?topic=Books" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">❧</div>
                <span>Books</span>
            </a>

            <a href="public-explore.php?topic=Artwork" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">❋</div>
                <span>Artwork</span>
            </a>

            <a href="public-explore.php?topic=Occult%20Studies" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">☼</div>
                <span>Occult Studies</span>
            </a>

            <a href="public-explore.php?topic=Others" class="topic-card">
                <div class="topic-card-visual" aria-hidden="true">◎</div>
                <span>Others</span>
            </a>
        </div>
    </section>

    <!-- LATEST COMMUNITY POSTS -->
    <section
        class="landing-section latest-posts-section"
        id="latest-posts"
    >
        <div class="section-heading">
            <div>
                <p class="section-eyebrow">Community</p>
                <h2>Latest from the Community</h2>

                <p class="section-description">
                    Discover what others have been sharing on Witchy.
                </p>
            </div>
        </div>

        <?php if (!empty($recentPosts)): ?>
            <div class="latest-posts-grid">
                <?php foreach ($recentPosts as $post): ?>
                    <?php
                    $postUrl = "post.php?id=" . (int) $post["post_id"];
                    $mediaFile = $post["media_file"] ?? "";
                    $mediaType = $post["media_type"] ?? "";

                    $mediaPath = $mediaFile !== ""
                        ? "uploads/posts/" . rawurlencode(basename($mediaFile))
                        : "";
                    ?>

                    <article class="latest-post-card">
                        <div class="latest-post-image">
                            <?php if ($mediaType === "image" && $mediaPath !== ""): ?>
                                <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>">
                                    <img
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                        loading="lazy"
                                    >
                                </a>

                            <?php elseif ($mediaType === "video" && $mediaPath !== ""): ?>
                                <video controls playsinline preload="metadata">
                                    <source
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                    >
                                    Your browser does not support video playback.
                                </video>

                            <?php else: ?>
                                <a
                                    href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>"
                                    class="latest-post-placeholder latest-post-text-preview"
                                >
                                    <span aria-hidden="true">✦</span>

                                    <p>
                                        <?php
                                        $preview = trim($post["description"] ?? "");

                                        if ($preview === "") {
                                            $preview = "Explore this post on Witchy";
                                        }

                                        echo htmlspecialchars(
                                            mb_strimwidth($preview, 0, 220, "..."),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </p>
                                </a>
                            <?php endif; ?>

                            <?php if ((int) $post["media_count"] > 1): ?>
                                <span class="latest-post-media-count">
                                    +<?= (int) $post["media_count"] - 1 ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="latest-post-content">
                            <?php if (!empty($post["topic"])): ?>
                                <span class="latest-post-topic">
                                    <?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                                </span>
                            <?php endif; ?>

                            <h3>
                                <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>">
                                    <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                                </a>
                            </h3>

                            <p class="latest-post-author">
                                @<?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="latest-posts-footer">
                <a
                    href="public-explore.php"
                    class="button button-secondary"
                >
                    Explore all posts →
                </a>
            </div>
        <?php else: ?>
            <p class="latest-posts-empty">
                No community posts yet. Check back soon!
            </p>
        <?php endif; ?>
    </section>

    <!-- EXPLORE / COMMUNITY -->
    <section class="landing-split-section">
        <article class="landing-info-card explore-card">
            <p class="section-eyebrow">Your pace</p>

            <h2>Explore at your own pace</h2>

            <p>
                Browse what interests you, save useful ideas,
                learn from different perspectives, or join the
                conversation when you feel like it.
            </p>

            <a href="#topics" class="text-link">
                Start exploring →
            </a>
        </article>

        <article
            class="landing-info-card community-card"
            id="community"
        >
            <p class="section-eyebrow">Community</p>

            <h2>Built around curiosity and respect</h2>

            <p>
                Different traditions, experiences and perspectives
                have a place here. Clear community rules and
                thoughtful moderation help keep conversations
                useful and respectful.
            </p>

            <a
                href="rules.php"
                class="button button-secondary"
            >
                Read community rules
            </a>
        </article>
    </section>

    <!-- FINAL CTA -->
    <section class="landing-final-cta">
        <h2>Find your place in Witchy</h2>

        <p>
            Explore what interests you,
            save what inspires you,
            and share what you know.
        </p>

        <div class="landing-hero-actions">
            <?php if (isset($_SESSION["user_id"])): ?>
                <a
                    href="#topics"
                    class="button button-primary button-large"
                >
                    Explore Witchy
                </a>
            <?php else: ?>
                <a
                    href="register.php"
                    class="button button-primary button-large"
                >
                    Join Witchy
                </a>

                <a
                    href="#topics"
                    class="button button-secondary button-large"
                >
                    Explore first
                </a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>