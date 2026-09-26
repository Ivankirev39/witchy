<?php

require_once __DIR__ . "/includes/session.php";


// The landing page is public.
//
// We only check whether the visitor is logged in
// so we can show the correct navigation.
$isLoggedIn = isset($_SESSION["user_id"]);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Witchy</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


    <!-- ========================================
         HEADER
         ======================================== -->

    <header class="site-header">


        <!-- LOGO -->

        <a
            href="index.php"
            class="site-logo"
            aria-label="Witchy home"
        >

            <span
                class="site-logo-symbol"
                aria-hidden="true"
            >
                ☾
            </span>

            <span>
                Witchy
            </span>

        </a>



        <!-- MAIN NAVIGATION -->

        <nav
            class="site-nav"
            aria-label="Main navigation"
        >


            <?php if ($isLoggedIn): ?>


                <a href="index.php">
                    Home
                </a>

                <a href="#topics">
                    Explore
                </a>

                <a href="create.php">
                    Create
                </a>

                <a href="saved.php">
                    Saved
                </a>

                <a href="profile.php">
                    Profile
                </a>


            <?php else: ?>


                <a href="#about">
                    About
                </a>

                <a href="#topics">
                    Explore
                </a>

                <a href="#community">
                    Community
                </a>


            <?php endif; ?>


        </nav>



        <!-- HEADER ACTIONS -->

        <div class="site-header-actions">


            <!-- THEME SWITCH -->

            <button
                type="button"
                class="theme-toggle site-theme-toggle"
                id="theme-toggle"
                aria-label="Switch to dark mode"
                title="Switch theme"
            >
                ☾
            </button>



            <?php if (!$isLoggedIn): ?>


                <a
                    href="login.php"
                    class="button button-secondary"
                >
                    Log in
                </a>


                <a
                    href="register.php"
                    class="button button-primary"
                >
                    Join Witchy
                </a>


            <?php endif; ?>


        </div>


    </header>



    <main>


        <!-- ========================================
             HERO
             ======================================== -->

        <section
            class="landing-hero"
            id="about"
        >


            <div class="landing-hero-content">


                <div
                    class="landing-symbol"
                    aria-hidden="true"
                >
                    ✦
                </div>


                <h1>
                    Explore what draws you in
                </h1>


                <p class="landing-tagline">
                    Share. Discover. Learn. Belong.
                </p>


                <p class="landing-intro">

                    Witchy is a visual community for exploring
                    witchcraft, spirituality, folklore and related
                    practices.

                    Discover ideas, learn from different perspectives,
                    and connect with people who share your interests.

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



            <!-- HERO VISUAL -->

            <div
                class="landing-hero-visual"
                aria-hidden="true"
            >


                <!--
                    Temporary visual.

                    Later this can be replaced with your
                    final Witchy photography.
                -->

                <div class="hero-image-placeholder">


                    <span class="hero-moon">
                        ☾
                    </span>


                    <span class="hero-star">
                        ✦
                    </span>


                    <p>
                        Witchy
                    </p>


                </div>


                <div class="hero-floating-symbol">
                    ✧
                </div>


            </div>


        </section>



        <!-- ========================================
             FOUR CORE PARTS
             ======================================== -->

        <section
            class="core-actions"
            aria-label="What you can do on Witchy"
        >


            <!-- SHARE -->

            <article class="core-action">


                <div
                    class="core-action-icon"
                    aria-hidden="true"
                >
                    ◇
                </div>


                <h2>
                    Share
                </h2>


                <p>
                    Ideas &amp; knowledge
                </p>


            </article>



            <!-- CONNECT -->

            <article class="core-action">


                <div
                    class="core-action-icon"
                    aria-hidden="true"
                >
                    ◎
                </div>


                <h2>
                    Connect
                </h2>


                <p>
                    With the community
                </p>


            </article>



            <!-- SAVE -->

            <article class="core-action">


                <div
                    class="core-action-icon"
                    aria-hidden="true"
                >
                    ♡
                </div>


                <h2>
                    Save
                </h2>


                <p>
                    Inspiration for later
                </p>


            </article>



            <!-- EXPLORE -->

            <article class="core-action">


                <div
                    class="core-action-icon"
                    aria-hidden="true"
                >
                    ✦
                </div>


                <h2>
                    Explore
                </h2>


                <p>
                    Topics that interest you
                </p>


            </article>


        </section>



        <!-- ========================================
             TOPICS
             ======================================== -->

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


                <!-- HERBS -->

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



                <!-- SPIRITUALITY -->

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



                <!-- RITUALS -->

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



                <!-- ASTROLOGY -->

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



                <!-- TAROT -->

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



                <!-- FOLK PRACTICES -->

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



        <!-- ========================================
             EXPLORE AT YOUR OWN PACE
             ======================================== -->

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



            <!-- ========================================
                 COMMUNITY
                 ======================================== -->

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
                    have a place here.

                    Clear community rules and thoughtful moderation
                    help keep conversations useful and respectful.

                </p>


                <!--
                    Change this to rules.php once
                    the Community Rules page exists.
                -->

                <a
                    href="#"
                    class="button button-secondary"
                >
                    Read community rules
                </a>


            </article>


        </section>



        <!-- ========================================
             FINAL CTA
             ======================================== -->

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



    <!-- ========================================
         FOOTER
         ======================================== -->

    <footer class="site-footer">


        <a
            href="index.php"
            class="site-logo"
            aria-label="Witchy home"
        >

            <span aria-hidden="true">
                ☾
            </span>

            Witchy

        </a>



        <nav
            class="footer-nav"
            aria-label="Footer navigation"
        >


            <a href="#about">
                About
            </a>


            <a href="#topics">
                Explore
            </a>


            <a href="#community">
                Community
            </a>


            <!--
                These can point to real pages later.
            -->

            <a href="#">
                Help
            </a>


            <a href="#">
                Contact
            </a>


            <a href="#">
                Privacy
            </a>


        </nav>



        <p class="footer-copy">

            © <?= date("Y") ?> Witchy

        </p>


    </footer>



    <!-- Existing Witchy theme JavaScript -->

    <script src="assets/js/auth.js"></script>


</body>

</html>