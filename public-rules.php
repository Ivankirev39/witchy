<?php
require_once __DIR__ . "/includes/session.php";

$pageTitle = "Community Rules | Witchy";
$pageCss = "public-rules.css";

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="public-rules-page">

    <section class="public-rules-hero">
        <p class="public-rules-eyebrow">Our community</p>

        <h1>Community Rules</h1>

        <p>
            How we keep Witchy respectful, welcoming and inspiring
            for everyone.
        </p>
    </section>

    <section class="public-rules-content">

        <article class="public-rule">
            <h2>1. Be respectful</h2>
            <p>
                Treat everyone with kindness. Different experiences,
                beliefs and opinions are welcome, but harassment,
                bullying and personal attacks are not.
            </p>
        </article>

        <article class="public-rule">
            <h2>2. Respect different paths</h2>
            <p>
                Witchcraft, spirituality and folklore mean different
                things to different people. Share your perspective
                without mocking or pressuring others.
            </p>
        </article>

        <article class="public-rule">
            <h2>3. Share thoughtfully</h2>
            <p>
                Keep posts relevant to the community, use suitable
                topics and provide context that helps others
                understand what you share.
            </p>
        </article>

        <article class="public-rule">
            <h2>4. Protect privacy</h2>
            <p>
                Do not share another person's private information
                or private conversations without permission.
                Be thoughtful about the personal details you post.
            </p>
        </article>

        <article class="public-rule">
            <h2>5. Keep content appropriate</h2>
            <p>
                Do not post threats, abusive content, scams or
                material intended to harm or exploit others.
            </p>
        </article>

        <article class="public-rule">
            <h2>6. Give credit</h2>
            <p>
                Respect creators by crediting original sources
                when sharing their work. Do not present another
                person's work as your own.
            </p>
        </article>

        <article class="public-rule">
            <h2>7. Avoid spam</h2>
            <p>
                Keep Witchy useful for everyone. Avoid repetitive
                posts, deceptive claims and unsolicited promotion.
            </p>
        </article>

        <article class="public-rule">
            <h2>8. Help keep Witchy welcoming</h2>
            <p>
                If you encounter content that concerns you, use
                the site's reporting options where available.
                Moderation action may be taken when rules are broken.
            </p>
        </article>

    </section>

    <section class="public-rules-cta">
        <h2>Ready to explore?</h2>

        <p>
            Discover topics, ideas and perspectives from across
            the Witchy community.
        </p>

        <div class="public-rules-actions">
            <a
                href="public-explore.php"
                class="button button-primary"
            >
                Explore Witchy
            </a>

            <a
                href="index.php"
                class="button button-secondary"
            >
                Back to home
            </a>
        </div>
    </section>

</main>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>