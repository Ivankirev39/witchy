<?php
require_once __DIR__ . "/includes/session.php";

$pageTitle = "Witchy";
$isLoggedIn = isset($_SESSION["user_id"]);

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
                <?php if ($isLoggedIn): ?>

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

        <div
            class="landing-hero-visual"
            aria-hidden="true"
        >
            <div class="hero-image-placeholder">
                <span class="hero-moon">☾</span>
                <span class="hero-star">✦</span>
                <p>Witchy</p>
            </div>

            <div class="hero-floating-symbol">
                ✧
            </div>
        </div>
    </section>


    <!-- FOUR CORE PARTS -->
    <section
        class="core-actions"
        aria-label="What you can do on Witchy"
    >

        <article class="core-action">
            <div
                class="core-action-icon"
                aria-hidden="true"
            >
                ◇
            </div>

            <h2>Share</h2>

            <p>
                Ideas &amp; knowledge
            </p>
        </article>

        <article class="core-action">
            <div
                class="core-action-icon"
                aria-hidden="true"
            >
                ◎
            </div>

            <h2>Connect</h2>

            <p>
                With the community
            </p>
        </article>

        <article class="core-action">
            <div
                class="core-action-icon"
                aria-hidden="true"
            >
                ♡
            </div>

            <h2>Save</h2>

            <p>
                Inspiration for later
            </p>
        </article>

        <article class="core-action">
            <div
                class="core-action-icon"
                aria-hidden="true"
            >
                ✦
            </div>

            <h2>Explore</h2>

            <p>
                Topics that interest you
            </p>
        </article>

    </section>


    <!-- TOPICS -->
    <section
        class="landing-section topics-section"
        id="topics"
    >

        <div class="section-heading">
            <div>
                <p class="section-eyebrow">
                    Discover
                </p>

                <h2>
                    What can you explore?
                </h2>

                <p class="section-description">
                    Follow your curiosity through topics,
                    practices and perspectives from across
                    the Witchy community.
                </p>
            </div>
        </div>


        <div class="topic-grid">

            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ✿
                </div>

                <span>
                    Herbs &amp; plants
                </span>
            </a>


            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ☾
                </div>

                <span>
                    Spirituality
                </span>
            </a>


            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ✦
                </div>

                <span>
                    Rituals &amp; spellwork
                </span>
            </a>


            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ☼
                </div>

                <span>
                    Astrology
                </span>
            </a>


            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ◇
                </div>

                <span>
                    Tarot &amp; oracle
                </span>
            </a>


            <a
                href="#"
                class="topic-card"
            >
                <div
                    class="topic-card-visual"
                    aria-hidden="true"
                >
                    ❧
                </div>

                <span>
                    Folk practices
                </span>
            </a>

        </div>

    </section>


    <!-- EXPLORE / COMMUNITY -->
    <section class="landing-split-section">

        <article class="landing-info-card explore-card">
            <p class="section-eyebrow">
                Your pace
            </p>

            <h2>
                Explore at your own pace
            </h2>

            <p>
                Browse what interests you, save useful ideas,
                learn from different perspectives, or join the
                conversation when you feel like it.
            </p>

            <a
                href="#topics"
                class="text-link"
            >
                Start exploring →
            </a>
        </article>


        <article
            class="landing-info-card community-card"
            id="community"
        >
            <p class="section-eyebrow">
                Community
            </p>

            <h2>
                Built around curiosity and respect
            </h2>

            <p>
                Different traditions, experiences and perspectives
                have a place here. Clear community rules and
                thoughtful moderation help keep conversations
                useful and respectful.
            </p>

            <a
                href="#"
                class="button button-secondary"
            >
                Read community rules
            </a>
        </article>

    </section>


    <!-- FINAL CTA -->
    <section class="landing-final-cta">

        <div
            class="cta-decoration"
            aria-hidden="true"
        >
            ☾
        </div>

        <h2>
            Find your place in Witchy
        </h2>

        <p>
            Explore what interests you,
            save what inspires you,
            and share what you know.
        </p>

        <div class="landing-hero-actions">

            <?php if ($isLoggedIn): ?>

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