<?php
require_once __DIR__ . "/includes/session.php";

$pageTitle = "Help & Support | Witchy";
$pageCss = "help.css";

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="help-page">

    <!-- HELP HERO -->
    <section class="help-hero" aria-labelledby="help-title">
        <span class="help-eyebrow">
            ✦ WITCHY HELP CENTER ✦
        </span>

        <h1 id="help-title">How can we help you?</h1>

        <p>
            New to Witchy or looking for a little guidance?
            Find answers to common questions about exploring
            our community and getting started.
        </p>

        <div class="help-search">
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-4-4"></path>
            </svg>

            <input
                type="search"
                id="help-search"
                placeholder="Search for answers..."
                aria-label="Search frequently asked questions"
            >
        </div>
    </section>


    <!-- FAQ SECTION -->
    <section class="help-faq" aria-labelledby="help-faq-title">

        <div class="help-section-heading">
            <span class="help-eyebrow">COMMON QUESTIONS</span>

            <h2 id="help-faq-title">
                Frequently asked questions
            </h2>

            <p>
                Everything you need to know about
                discovering and joining Witchy.
            </p>
        </div>


        <!-- FAQ CATEGORIES -->
        <div class="help-categories"
             aria-label="Filter frequently asked questions">

            <button
                type="button"
                class="help-category active"
                data-category="all"
                aria-pressed="true">
                All questions
            </button>

            <button
                type="button"
                class="help-category"
                data-category="getting-started"
                aria-pressed="false">
                Getting Started
            </button>

            <button
                type="button"
                class="help-category"
                data-category="account"
                aria-pressed="false">
                Accounts
            </button>

            <button
                type="button"
                class="help-category"
                data-category="community"
                aria-pressed="false">
                Community
            </button>
        </div>


        <!-- FAQ QUESTIONS -->
        <div class="help-faq-list">

            <!-- GETTING STARTED -->

            <details class="help-faq-item"
                     data-category="getting-started">
                <summary>What is Witchy?</summary>

                <div class="help-faq-answer">
                    Witchy is a visual community for people
                    interested in witchcraft, spirituality,
                    folklore and related practices.

                    It's a place to discover ideas, explore
                    different perspectives and connect with
                    people who share your interests.

                    <a href="about.php">Learn more about Witchy</a>.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="getting-started">
                <summary>Can I explore Witchy without an account?</summary>

                <div class="help-faq-answer">
                    Yes! You can explore public posts, browse
                    different topics and discover what the
                    community has been sharing without
                    creating an account.

                    <a href="public-explore.php">Start exploring</a>.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="getting-started">
                <summary>Do I need experience with witchcraft or spirituality?</summary>

                <div class="help-faq-answer">
                    Not at all! Witchy welcomes beginners,
                    curious explorers and experienced
                    practitioners alike.

                    You don't need to follow any particular
                    belief or tradition to be part of
                    the community.
                </div>
            </details>


            <!-- ACCOUNTS -->

            <details class="help-faq-item"
                     data-category="account">
                <summary>How do I create an account?</summary>

                <div class="help-faq-answer">
                    Select
                    <a href="register.php">Join Witchy</a>
                    to open the registration page.

                    Follow the instructions to choose
                    your username, enter your email
                    address and create a password.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="account">
                <summary>Is creating an account free?</summary>

                <div class="help-faq-answer">
                    Yes. Creating a Witchy account is free.

                    Once registered, you can access
                    community features such as liking,
                    saving and commenting on posts.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="account">
                <summary>What if I already have an account?</summary>

                <div class="help-faq-answer">
                    If you've registered before, simply
                    visit the
                    <a href="login.php">Log in</a>
                    page and enter your credentials.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="account">
                <summary>Can I delete my account?</summary>

                <div class="help-faq-answer">
                    Yes. Registered members can request
                    account deletion through their
                    account settings.

                    Account deletion may permanently
                    remove associated community content.
                    Please review the
                    <a href="privacy.php">Privacy Policy</a>
                    for more information.
                </div>
            </details>


            <!-- COMMUNITY -->

            <details class="help-faq-item"
                     data-category="community">
                <summary>Can I like, save or comment without registering?</summary>

                <div class="help-faq-answer">
                    No. Visitors can browse public content,
                    but interacting with posts requires
                    a Witchy account.

                    Once you've registered and logged in,
                    you can like posts, save inspiration,
                    comment and follow other members.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="community">
                <summary>What topics can I explore?</summary>

                <div class="help-faq-answer">
                    Witchy covers a range of interests,
                    including spells, altars, tarot,
                    herbs, crystals, books, artwork
                    and occult studies.

                    Explore the available topics on
                    the <a href="public-explore.php">Explore page</a>.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="community">
                <summary>Can I share my own posts?</summary>

                <div class="help-faq-answer">
                    Yes! Registered members can create
                    posts and share their knowledge,
                    creative work or experiences.

                    Posts can include text, images
                    and supported videos.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="community">
                <summary>Does Witchy have community rules?</summary>

                <div class="help-faq-answer">
                    Yes. Witchy encourages respectful
                    conversations, responsible sharing
                    and appreciation of different beliefs
                    and traditions.

                    Read our
                    <a href="public-rules.php">Community Rules</a>
                    to learn more.
                </div>
            </details>

            <details class="help-faq-item"
                     data-category="community">
                <summary>How is my information handled?</summary>

                <div class="help-faq-answer">
                    Our Privacy Policy explains what
                    information the platform collects,
                    how it is used and the rights
                    available to users.

                    <a href="privacy.php">Read our Privacy Policy</a>.
                </div>
            </details>

        </div>


        <!-- NO SEARCH RESULTS -->
        <p
            class="help-no-results"
            id="help-no-results"
            role="status"
            hidden
        >
            No matching questions found.
            Try searching for something else.
        </p>

    </section>


    <!-- CONTACT SUPPORT -->
    <section class="help-support"
             aria-labelledby="help-support-title">

        <div class="help-support-icon" aria-hidden="true">
            ✦
        </div>

        <h2 id="help-support-title">
            Still need a little guidance?
        </h2>

        <p>
            Didn't find what you were looking for?
            Whether you have a question, need assistance
            or want to learn more about our community,
            we're here to help.
        </p>

        <div class="help-support-actions">

            <a
                href="public-contact.php"
                class="help-button help-button-primary"
            >
                Contact Support
                <span aria-hidden="true">→</span>
            </a>

            <a
                href="public-rules.php"
                class="help-button help-button-secondary"
            >
                Community Rules
                <span aria-hidden="true">→</span>
            </a>

        </div>

    </section>

</main>

<script src="assets/js/help.js" defer></script>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>
