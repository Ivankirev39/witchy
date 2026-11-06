<?php
require_once __DIR__ . "/includes/session.php";

$pageTitle = "Community | Witchy";
$pageCss = "community.css";

require_once __DIR__ . "/models/DBConnector.php";

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

// Retrieve three recent community posts.
$communityPosts = [];

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
        ) AS media_type

    FROM post p

    INNER JOIN users u
        ON u.user_id = p.user_id

    ORDER BY p.created_at DESC, p.post_id DESC

    LIMIT 3
";

$stmt = $pdo->query($sql);
$communityPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Safely output HTML text.
function communityEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$isCommunityMember = isset($_SESSION["user_id"]);

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="community-page">

    <!-- ========================================
         1. COMMUNITY HERO
         ======================================== -->

    <section class="community-hero">

        <div class="community-hero-content">

            <p class="community-eyebrow">
                ✦ THE WITCHY COMMUNITY
            </p>

            <h1>Every path has a story.</h1>

            <p class="community-hero-tagline">
                Different paths. Shared curiosity.
            </p>

            <p class="community-hero-description">
                Witchy is shaped by the people who share
                their knowledge, creativity and experiences.

                Discover different perspectives, find
                inspiration and become part of a community
                that celebrates curiosity and connection.
            </p>

            <div class="community-actions">

                <a
                    href="public-explore.php"
                    class="button button-primary button-large"
                >
                    Explore the community
                </a>

                <?php if (!$isCommunityMember): ?>
                    <a
                        href="register.php"
                        class="button button-secondary button-large"
                    >
                        Join Witchy
                    </a>
                <?php else: ?>
                    <a
                        href="feed.php"
                        class="button button-secondary button-large"
                    >
                        Your feed
                    </a>
                <?php endif; ?>

            </div>

        </div>

        <div class="community-hero-visual">
            <img
                src="images/community-visual.png"
                alt="Four people gathered around a table, sharing tarot cards and conversation"
                loading="eager"
            >
        </div>

    </section>


    <!-- ========================================
         2. COMMUNITY INTRODUCTION
         ======================================== -->

    <section class="community-introduction">

        <div class="community-container community-intro-grid">

            <div>
                <p class="community-eyebrow">
                    THE PEOPLE BEHIND THE MAGIC
                </p>

                <h2>
                    A community shaped by its members
                </h2>
            </div>

            <div class="community-intro-text">

                <p>
                    Everyone comes to Witchy with different
                    interests, experiences and reasons
                    for exploring.
                </p>

                <p>
                    Some are discovering new practices,
                    others are learning from shared knowledge,
                    and many enjoy contributing their own
                    ideas and creativity.
                </p>

                <p>
                    There is no single way to belong here.
                    Whether you're curious, experienced
                    or somewhere in between, your
                    perspective has a place.
                </p>

            </div>

        </div>

    </section>


    <!-- ========================================
         3. MEET THE COMMUNITY
         ======================================== -->

    <section class="community-members">

        <div class="community-container">

            <div class="community-section-heading">

                <p class="community-eyebrow">
                    MEET THE COMMUNITY
                </p>

                <h2>Different ways to belong</h2>

                <p>
                    Every member brings something different
                    to Witchy. Here are some of the ways
                    people explore and participate.
                </p>

            </div>


            <div class="community-member-grid">

                <!-- EXPLORER -->
                <article class="community-member-card">

                    <div class="community-member-symbol"
                         aria-hidden="true">
                        ✦
                    </div>

                    <h3>The Explorer</h3>

                    <p>
                        Curious about different topics,
                        traditions and ideas. Explorers
                        enjoy discovering what interests
                        them at their own pace.
                    </p>

                </article>


                <!-- LEARNER -->
                <article class="community-member-card">

                    <div class="community-member-symbol"
                         aria-hidden="true">
                        ☾
                    </div>

                    <h3>The Learner</h3>

                    <p>
                        Interested in developing knowledge,
                        asking questions and learning from
                        the experiences of others.
                    </p>

                </article>


                <!-- CONTRIBUTOR -->
                <article class="community-member-card">

                    <div class="community-member-symbol"
                         aria-hidden="true">
                        ◇
                    </div>

                    <h3>The Contributor</h3>

                    <p>
                        Enjoys sharing ideas, artwork,
                        experiences and knowledge that
                        may inspire other members.
                    </p>

                </article>


                <!-- PRACTITIONER -->
                <article class="community-member-card">

                    <div class="community-member-symbol"
                         aria-hidden="true">
                        ✧
                    </div>

                    <h3>The Practitioner</h3>

                    <p>
                        Brings personal experiences and
                        established practices to the
                        community while exploring
                        different perspectives.
                    </p>

                </article>

            </div>

        </div>

    </section>

        <!-- ========================================
         3b. WHAT YOU'LL FIND HERE (from About)
         ======================================== -->

    <section class="community-features">
        <div class="community-container">

            <div class="community-section-heading">
                <p class="community-eyebrow">WHAT YOU'LL FIND HERE</p>
                <h2>Explore. Learn. Connect.</h2>
                <p>Make Witchy your own, whatever brings you here.</p>
            </div>

            <div class="community-feature-grid">

                <article class="community-member-card">
                    <div class="community-member-symbol" aria-hidden="true">✦</div>
                    <h3>Explore</h3>
                    <p>Discover posts, creative ideas and perspectives across topics that interest you.</p>
                </article>

                <article class="community-member-card">
                    <div class="community-member-symbol" aria-hidden="true">◇</div>
                    <h3>Learn &amp; share</h3>
                    <p>Find inspiration in others' experiences and, as a member, contribute your own knowledge.</p>
                </article>

                <article class="community-member-card">
                    <div class="community-member-symbol" aria-hidden="true">☾</div>
                    <h3>Connect</h3>
                    <p>Join conversations, follow people who inspire you and save posts to revisit later.</p>
                </article>

            </div>
        </div>
    </section>


    <!-- ========================================
         4. COMMUNITY POSTS
         ======================================== -->

    <section class="community-posts"
             id="community-posts">

        <div class="community-container">

            <div class="community-section-heading">

                <p class="community-eyebrow">
                    FROM THE COMMUNITY
                </p>

                <h2>What people are sharing</h2>

                <p>
                    Discover a glimpse of the ideas,
                    creativity and experiences shared
                    by the Witchy community.
                </p>

            </div>


            <?php if (!empty($communityPosts)): ?>

                <div class="community-post-grid">

                    <?php foreach ($communityPosts as $post): ?>

                        <?php
                        $postUrl = "post.php?id="
                            . (int) $post["post_id"];

                        $mediaFile = trim(
                            (string) ($post["media_file"] ?? "")
                        );

                        $mediaType = strtolower(trim(
                            (string) ($post["media_type"] ?? "")
                        ));

                        $mediaPath = $mediaFile !== ""
                            ? "uploads/posts/"
                                . rawurlencode(basename($mediaFile))
                            : "";

                        $description = trim(
                            (string) ($post["description"] ?? "")
                        );

                        $previewText = $description !== ""
                            ? mb_strimwidth(
                                $description,
                                0,
                                200,
                                "..."
                            )
                            : "Discover this community post on Witchy.";
                        ?>

                        <article class="community-post-card">

                            <div class="community-post-media">

                                <?php if (
                                    $mediaType === "image" &&
                                    $mediaPath !== ""
                                ): ?>

                                    <a href="<?= communityEscape($postUrl) ?>">

                                        <img
                                            src="<?= communityEscape($mediaPath) ?>"
                                            alt="<?= communityEscape($post["title"]) ?>"
                                            loading="lazy"
                                        >

                                    </a>

                                <?php elseif (
                                    $mediaType === "video" &&
                                    $mediaPath !== ""
                                ): ?>

                                    <a
                                        href="<?= communityEscape($postUrl) ?>"
                                        class="community-video-preview"
                                    >
                                        <span aria-hidden="true">▶</span>
                                        <span>View video post</span>
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="<?= communityEscape($postUrl) ?>"
                                        class="community-post-placeholder"
                                    >
                                        <span aria-hidden="true">✦</span>

                                        <p>
                                            <?= communityEscape($previewText) ?>
                                        </p>
                                    </a>

                                <?php endif; ?>

                            </div>


                            <div class="community-post-content">

                                <?php if (!empty($post["topic"])): ?>

                                    <span class="community-post-topic">
                                        <?= communityEscape($post["topic"]) ?>
                                    </span>

                                <?php endif; ?>


                                <h3>
                                    <a href="<?= communityEscape($postUrl) ?>">
                                        <?= communityEscape(
                                            $post["title"] ?: "Untitled post"
                                        ) ?>
                                    </a>
                                </h3>


                                <p class="community-post-author">
                                    @<?= communityEscape($post["username"]) ?>
                                </p>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <div class="community-posts-footer">

                    <a
                        href="public-explore.php"
                        class="button button-secondary"
                    >
                        Explore all posts →
                    </a>

                </div>

            <?php else: ?>

                <div class="community-posts-empty">

                    <span aria-hidden="true">✦</span>

                    <h3>Our story is just beginning</h3>

                    <p>
                        Community posts will appear here
                        as members begin sharing.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ========================================
         5. COMMUNITY VALUES
         ======================================== -->

    <section class="community-values">

        <div class="community-container community-values-grid">

            <div class="community-values-visual">
                <img
                    src="images/community-values.png"
                    alt="A group of friends sharing tarot cards and enjoying a conversation together"
                    loading="lazy"
                >
            </div>

            <div class="community-values-content">

                <p class="community-eyebrow">
                    BELONGING & RESPECT
                </p>

                <h2>
                    Different beliefs.<br>
                    Shared respect.
                </h2>

                <p>
                    Witchy welcomes different backgrounds,
                    experiences, traditions and levels
                    of knowledge.
                </p>

                <p>
                    We encourage thoughtful conversations,
                    responsible sharing and respect for
                    the perspectives of others.
                </p>

                <div class="community-value-tags">

                    <span>Curiosity</span>
                    <span>Respect</span>
                    <span>Openness</span>
                    <span>Creativity</span>

                </div>

                <a
                    href="public-rules.php"
                    class="button button-secondary"
                >
                    Read community rules →
                </a>

            </div>

        </div>

    </section>


    <!-- ========================================
         6. JOIN COMMUNITY
         ======================================== -->

    <section class="community-final-cta">

        <p class="community-eyebrow">
            YOUR PLACE IN WITCHY
        </p>

        <h2>Find your people.</h2>

        <p>
            Discover new ideas, share what inspires
            you and join conversations that matter
            to you.
        </p>

        <div class="community-actions">

            <?php if (!$isCommunityMember): ?>

                <a
                    href="register.php"
                    class="button button-primary button-large"
                >
                    Join Witchy
                </a>

                <a
                    href="public-explore.php"
                    class="button button-secondary button-large"
                >
                    Explore first
                </a>

            <?php else: ?>

                <a
                    href="feed.php"
                    class="button button-primary button-large"
                >
                    Go to your feed
                </a>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>
