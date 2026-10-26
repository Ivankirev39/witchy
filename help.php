
<?php
require_once __DIR__ . "/includes/auth.php";
$pageTitle = "Help & Support | Witchy";
$pageCss = "help.css";
require_once __DIR__ . "/includes/user_header.php";
?>
<main class="help-page">
    <section class="help-hero">
        <span class="help-eyebrow">✦ WITCHY HELP CENTER ✦</span>
        <h1>How can we help you?</h1>
        <p>Find answers to common questions and learn more about your Witchy experience.</p>
        <div class="help-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-4-4"></path>
            </svg>
            <input type="search" id="help-search" placeholder="Search for answers..." aria-label="Search frequently asked questions">
        </div>
    </section>
    <section class="help-faq" aria-labelledby="help-faq-title">
        <div class="help-section-heading">
            <span class="help-eyebrow">COMMON QUESTIONS</span>
            <h2 id="help-faq-title">Frequently asked questions</h2>
            <p>A little guidance for your journey through Witchy.</p>
        </div>
        <div class="help-categories" aria-label="Filter frequently asked questions">
            <button type="button" class="help-category active" data-category="all" aria-pressed="true">All questions</button>
            <button type="button" class="help-category" data-category="account" aria-pressed="false">Account</button>
            <button type="button" class="help-category" data-category="posts" aria-pressed="false">Posts</button>
            <button type="button" class="help-category" data-category="community" aria-pressed="false">Community</button>
        </div>
        <div class="help-faq-list">
            <details class="help-faq-item" data-category="account">
                <summary>How do I edit my profile?</summary>
                <div class="help-faq-answer">
                    Visit your profile and select <strong>Edit Profile</strong>. From there, you can update your information and profile picture.
                </div>
            </details>
            <details class="help-faq-item" data-category="posts">
                <summary>How do I create a post?</summary>
                <div class="help-faq-answer">
                    Select <strong>Create Post</strong> from the navigation menu. Add a title, choose a topic, write your content, and publish your post.
                </div>
            </details>
            <details class="help-faq-item" data-category="posts">
                <summary>Can I upload images and videos?</summary>
                <div class="help-faq-answer">
                    Yes! You can upload up to five images or one video per post. You can also share text-only posts without any media.
                </div>
            </details>
            <details class="help-faq-item" data-category="posts">
                <summary>How do I delete my post?</summary>
                <div class="help-faq-answer">
                    Open a post you've created, select <strong>Delete</strong>, and confirm your choice. Remember that deleted posts cannot be recovered.
                </div>
            </details>
            <details class="help-faq-item" data-category="community">
                <summary>How do I save a post?</summary>
                <div class="help-faq-answer">
                    Click the bookmark icon on a post to save it for later. You can find your collection on the <a href="saved.php">Saved</a> page.
                </div>
            </details>
            <details class="help-faq-item" data-category="community">
                <summary>How do I follow other users?</summary>
                <div class="help-faq-answer">
                    Visit another user's profile and click <strong>Follow</strong>. You can also follow users directly from their posts.
                </div>
            </details>
        </div>
        <p class="help-no-results" id="help-no-results" role="status" hidden>No matching questions found. Try searching for something else.</p>
    </section>
    <section class="help-support" aria-labelledby="help-support-title">
        <div class="help-support-icon" aria-hidden="true">✦</div>
        <h2 id="help-support-title">Still need a little guidance?</h2>
        <p>Didn't find what you were looking for? Whether you have a question, need assistance, or want to learn more about our community, we're here to guide you.</p>
        <div class="help-support-actions">
            <a href="contact.php" class="help-button help-button-primary">
                Contact Support
                <span aria-hidden="true">→</span>
            </a>
            <a href="rules.php" class="help-button help-button-secondary">
                Community Rules
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
</main>
<script src="assets/js/help.js" defer></script>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
