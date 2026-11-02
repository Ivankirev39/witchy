<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/models/ContactRateLimiter.php";

$contactRateLimiter = new ContactRateLimiter($conn);

$pageTitle = "Contact Support | Witchy";
$pageCss = "contact.css";

$errors = [];

$success = !empty($_SESSION["public_contact_success"]);
unset($_SESSION["public_contact_success"]);

$name = "";
$email = "";
$category = "";
$subject = "";
$message = "";

$categories = [
    "account" => "Account & Registration",
    "technical" => "Technical Problem",
    "community" => "Community & Content",
    "report" => "Report an Issue",
    "other" => "Other Question"
];

// Prepare the form timer when the page is opened.
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $_SESSION["public_contact_started"] = time();
}

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Read only string values from the form.
    $fields = [
        "name",
        "email",
        "category",
        "subject",
        "message",
        "website"
    ];

    $input = [];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? "";
        $input[$field] = is_string($value)
            ? trim($value)
            : "";
    }

    $name = $input["name"];
    $email = $input["email"];
    $category = $input["category"];
    $subject = $input["subject"];
    $message = $input["message"];

    // CSRF VALIDATION
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        $errors[] = "Invalid request. Please refresh and try again.";
    }

    // HONEYPOT
    if ($input["website"] !== "") {
        $errors[] = "Your request could not be processed.";
    }

    // MINIMUM COMPLETION TIME
    $started = $_SESSION["public_contact_started"] ?? null;

    if (
        !is_int($started) ||
        time() - $started < 3
    ) {
        $errors[] = "Please refresh the page and try again.";
    }

    // SESSION COOLDOWN: ONE REQUEST PER MINUTE
    $lastSent = $_SESSION["public_contact_last_sent"] ?? 0;

    if (time() - (int) $lastSent < 60) {
        $errors[] = "Please wait a minute before sending another message.";
    }

    // VALIDATE NAME
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = "Your name must be between 2 and 100 characters.";
    }

    // VALIDATE EMAIL
    if (
        strlen($email) > 254 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = "Please enter a valid email address.";
    }

    // VALIDATE CATEGORY
    if (!array_key_exists($category, $categories)) {
        $errors[] = "Please select a support category.";
    }

    // VALIDATE SUBJECT
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
        $errors[] = "Your subject must be between 3 and 150 characters.";
    }

    // VALIDATE MESSAGE
    if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $errors[] = "Your message must be between 10 and 5000 characters.";
    }

    // SAVE TO DATABASE
    if (empty($errors)) {

        try {
            $submitted = $contactRateLimiter->submitContactRequest(
                null,
                $name,
                $email,
                $category,
                $subject,
                $message,
                $_SERVER["REMOTE_ADDR"] ?? ""
            );

            if (!$submitted) {
                $errors[] = "You've sent too many messages. Please try again in 15 minutes.";
            } else {
                $_SESSION["public_contact_last_sent"] = time();
                $_SESSION["public_contact_success"] = true;

                // Avoid duplicate submissions on refresh.
                header("Location: public-contact.php", true, 303);
                exit;
            }
        } catch (Throwable $e) {

            error_log(
                "Public contact form error: " . $e->getMessage()
            );

            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="contact-page">

    <!-- CONTACT HERO -->
    <header class="contact-hero">

        <span class="contact-eyebrow">
            ✦ WITCHY SUPPORT ✦
        </span>

        <h1>We're here to help</h1>

        <p>
            Have a question, found a problem, or need a
            little guidance? We'd love to hear from you.
        </p>

    </header>

    <div class="contact-layout">

        <!-- CONTACT FORM -->
        <section class="contact-form-card">

            <div class="contact-section-heading">

                <span class="contact-eyebrow">GET IN TOUCH</span>

                <h2>Send us a message</h2>

                <p>
                    Fill out the form below and we'll review
                    your request. No account required.
                </p>

            </div>

            <!-- SUCCESS MESSAGE -->
            <?php if ($success): ?>

                <div class="contact-success" role="status">

                    <strong>Message sent successfully!</strong>

                    <p>
                        Your request has been received.
                        Thank you for reaching out to Witchy.
                    </p>

                </div>

            <?php endif; ?>

            <!-- ERROR MESSAGES -->
            <?php if (!empty($errors)): ?>

                <div class="contact-errors" role="alert">

                    <strong>Please check the following:</strong>

                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                </div>

            <?php endif; ?>

            <!-- FORM -->
            <form
                action="public-contact.php"
                method="POST"
                class="contact-form">

                <!-- CSRF TOKEN -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                                csrf_token(),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>">

                <!-- BOT HONEYPOT -->
                <div class="contact-honeypot" aria-hidden="true">

                    <label for="contact-website">
                        Leave this field empty
                    </label>

                    <input
                        type="text"
                        id="contact-website"
                        name="website"
                        tabindex="-1"
                        autocomplete="off">

                </div>

                <!-- NAME + EMAIL -->
                <div class="contact-form-row">

                    <div class="contact-field">

                        <label for="contact-name">
                            Your name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="contact-name"
                            name="name"
                            value="<?= htmlspecialchars(
                                        $name,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                            placeholder="Your name"
                            maxlength="100"
                            autocomplete="name"
                            required>

                    </div>

                    <div class="contact-field">

                        <label for="contact-email">
                            Email address <span>*</span>
                        </label>

                        <input
                            type="email"
                            id="contact-email"
                            name="email"
                            value="<?= htmlspecialchars(
                                        $email,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                            placeholder="you@example.com"
                            maxlength="254"
                            autocomplete="email"
                            required>

                    </div>

                </div>

                <!-- CATEGORY -->
                <div class="contact-field">

                    <label for="contact-category">
                        What do you need help with? <span>*</span>
                    </label>

                    <select
                        id="contact-category"
                        name="category"
                        required>

                        <option
                            value=""
                            disabled
                            <?= $category === "" ? "selected" : "" ?>>
                            Select a topic
                        </option>

                        <?php foreach ($categories as $value => $label): ?>

                            <option
                                value="<?= htmlspecialchars(
                                            $value,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                <?= $category === $value ? "selected" : "" ?>>
                                <?= htmlspecialchars(
                                    $label,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- SUBJECT -->
                <div class="contact-field">

                    <label for="contact-subject">
                        Subject <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="contact-subject"
                        name="subject"
                        value="<?= htmlspecialchars(
                                    $subject,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                        placeholder="Briefly describe your question or issue"
                        maxlength="150"
                        required>

                </div>

                <!-- MESSAGE -->
                <div class="contact-field">

                    <label for="contact-message">
                        Your message <span>*</span>
                    </label>

                    <textarea
                        id="contact-message"
                        name="message"
                        rows="7"
                        maxlength="5000"
                        placeholder="Tell us a little more about what you need help with..."
                        required><?= htmlspecialchars(
                                        $message,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?></textarea>

                    <span class="contact-field-hint">
                        Please don't include passwords or other
                        sensitive personal information.
                        See our
                        <a href="privacy.php">Privacy Policy</a>.
                    </span>

                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="contact-submit">
                    Send message
                    <span aria-hidden="true">→</span>
                </button>

            </form>

        </section>

        <!-- SIDEBAR -->
        <aside class="contact-sidebar">

            <!-- HELPFUL LINKS -->
            <div class="contact-info-card">

                <div class="contact-info-symbol" aria-hidden="true">
                    ✦
                </div>

                <h2>A little guidance</h2>

                <p>
                    Before sending a message, you might find
                    the answer you're looking for in our Help Center.
                </p>

                <a href="public-help.php" class="contact-info-link">
                    <span>Help &amp; FAQ</span>
                    <span aria-hidden="true">→</span>
                </a>

                <a href="public-rules.php" class="contact-info-link">
                    <span>Community Rules</span>
                    <span aria-hidden="true">→</span>
                </a>

                <a href="about.php" class="contact-info-link">
                    <span>About Witchy</span>
                    <span aria-hidden="true">→</span>
                </a>

            </div>

            <!-- FRIENDLY REMINDER -->
            <div class="contact-note-card">

                <h3>✧ A friendly reminder</h3>

                <p>
                    Witchy is a place for curiosity, learning
                    and respectful conversations. Thank you
                    for helping us make our community better.
                </p>

            </div>

        </aside>

    </div>

</main>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>