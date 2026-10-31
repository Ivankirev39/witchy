<?php
require_once __DIR__ . "/includes/session.php";

$pageTitle = "About Witchy | Witchy";
$pageCss = "about.css";

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="about-page">

    <!-- HERO -->
    <section class="about-hero" aria-labelledby="about-title">

        <div class="about-hero-content">
            <p class="about-eyebrow">✦ GET TO KNOW WITCHY</p>

            <h1 id="about-title">A space for the curious.</h1>

            <p class="about-hero-tagline">
                Different paths. Shared curiosity.
            </p>

            <p class="about-hero-description">
                Witchy is a visual community for people interested in
                witchcraft, spirituality, folklore and related practices.
                Whether you're discovering something new or sharing years
                of experience, there's room to explore at your own pace.
            </p>

            <div class="about-actions">
                <a href="public-explore.php"
                   class="button button-primary button-large">
                    Explore Witchy
                </a>

                <a href="register.php"
                   class="button button-secondary button-large">
                    Join the community
                </a>
            </div>
        </div>

        <div class="about-hero-visual">
            <img
                src="images/witchy-hero.png"
                alt="An arrangement of herbs, tarot cards, crystals and candles"
                loading="eager"
            >
        </div>

    </section>


    <!-- INTRODUCTION -->
    <section class="about-intro about-container"
             aria-labelledby="about-community-title">

        <div>
            <p class="about-eyebrow">OUR COMMUNITY</p>

            <h2 id="about-community-title">
                A place to discover and belong
            </h2>
        </div>

        <div class="about-intro-copy">
            <p>
                Witchy brings together different interests, traditions
                and perspectives in one welcoming space. From herbs and
                tarot to rituals, astrology, folklore and personal
                practices, the community is shaped by what its members
                choose to share.
            </p>

            <p>
                You don't need to follow a particular tradition or have
                previous experience to be here. Browse ideas, learn from
                others, and discover the topics that speak to you.
            </p>
        </div>

    </section>


    <!-- COMMUNITY FEATURES -->
    <section class="about-features"
             aria-labelledby="about-features-title">

        <div class="about-container">

            <div class="about-section-heading">
                <p class="about-eyebrow">WHAT YOU'LL FIND HERE</p>

                <h2 id="about-features-title">
                    Explore. Learn. Connect.
                </h2>

                <p>
                    Make Witchy your own, whatever brings you here.
                </p>
            </div>

            <div class="about-feature-grid">

                <article class="about-feature-card">
                    <span class="about-feature-icon"
                          aria-hidden="true">✦</span>

                    <h3>Explore</h3>

                    <p>
                        Discover posts, creative ideas and perspectives
                        across topics that interest you.
                    </p>
                </article>

                <article class="about-feature-card">
                    <span class="about-feature-icon"
                          aria-hidden="true">◇</span>

                    <h3>Learn &amp; share</h3>

                    <p>
                        Find inspiration in others' experiences and,
                        as a member, contribute your own knowledge.
                    </p>
                </article>

                <article class="about-feature-card">
                    <span class="about-feature-icon"
                          aria-hidden="true">☾</span>

                    <h3>Connect</h3>

                    <p>
                        Join conversations, follow people who inspire
                        you and save posts to revisit later.
                    </p>
                </article>

            </div>
        </div>

    </section>


    <!-- COMMUNITY VALUES -->
    <section class="about-values about-container"
             aria-labelledby="about-values-title">

        <div class="about-values-decor" aria-hidden="true">
            ✧
        </div>

        <div>
            <p class="about-eyebrow">WHAT MATTERS TO US</p>

            <h2 id="about-values-title">
                Curiosity, openness and respect.
            </h2>

            <p>
                Different experiences, beliefs and traditions have a
                place at Witchy. We encourage thoughtful discussions,
                crediting sources, respecting personal boundaries
                and learning from one another.
            </p>

            <p>
                Our community rules help keep this a welcoming
                space for everyone.
            </p>

            <a href="public-rules.php"
               class="button button-secondary">
                Read community rules →
            </a>
        </div>

    </section>


    <!-- FINAL CALL TO ACTION -->
    <section class="about-final-cta"
             aria-labelledby="about-cta-title">

        <p class="about-eyebrow">YOUR JOURNEY STARTS HERE</p>

        <h2 id="about-cta-title">
            Find your place in Witchy
        </h2>

        <p>
            Take a look around, follow your curiosity
            and join when you're ready.
        </p>

        <div class="about-actions">
            <a href="register.php"
               class="button button-primary button-large">
                Join Witchy
            </a>

            <a href="public-explore.php"
               class="button button-secondary button-large">
                Explore first
            </a>
        </div>

    </section>

</main>

<?php require_once __DIR__ . "/includes/public-footer.php"; ?>
