<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Contact Support | Witchy";
$pageCss = "contact.css";
$userId = (int) $_SESSION["user_id"];
$errors = [];
$success = !empty($_SESSION["contact_success"]);
unset($_SESSION["contact_success"]);

$userStmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$name = $user["username"] ?? "";
$email = $user["email"] ?? "";
$category = "";
$subject = "";
$message = "";

$categories = [
    "account" => "Account & Profile",
    "technical" => "Technical Problem",
    "posts" => "Posts & Content",
    "report" => "Report an Issue",
    "other" => "Other Question"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $category = $_POST["category"] ?? "";
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Please enter a name between 2 and 100 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = "Please enter a valid email address.";
    }
    if (!array_key_exists($category, $categories)) {
        $errors[] = "Please select a support category.";
    }
    if (strlen($subject) < 3 || strlen($subject) > 150) {
        $errors[] = "Your subject must be between 3 and 150 characters.";
    }
    if (strlen($message) < 10 || strlen($message) > 5000) {
        $errors[] = "Your message must be between 10 and 5000 characters.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO support_request
                (user_id, name, email, category, subject, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $name,
                $email,
                $category,
                $subject,
                $message
            ]);
            $_SESSION["contact_success"] = true;
            header("Location: contact.php");
            exit;
        } catch (PDOException $e) {
            error_log("Support request failed: " . $e->getMessage());
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>
<section class="contact-page">
    <header class="contact-hero">
        <span class="contact-eyebrow">✦ WITCHY SUPPORT ✦</span>
        <h1>We're here to help</h1>
        <p>Have a question, found a problem, or need a little guidance? We'd love to hear from you.</p>
    </header>
    <div class="contact-layout">
        <section class="contact-form-card">
            <div class="contact-section-heading">
                <span class="contact-eyebrow">GET IN TOUCH</span>
                <h2>Send us a message</h2>
                <p>Fill out the form below and we'll review your request.</p>
            </div>
            <?php if ($success): ?>
                <div class="contact-success" role="status">
                    <strong>Message sent successfully!</strong>
                    <p>Your request has been received. Thank you for reaching out to Witchy.</p>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="contact-errors" role="alert">
                    <strong>Please check the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form action="contact.php" method="POST" class="contact-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                <div class="contact-form-row">
                    <div class="contact-field">
                        <label for="contact-name">Your name <span>*</span></label>
                        <input type="text" id="contact-name" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>" placeholder="Your name" maxlength="100" required>
                    </div>
                    <div class="contact-field">
                        <label for="contact-email">Email address <span>*</span></label>
                        <input type="email" id="contact-email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" placeholder="you@example.com" maxlength="254" required>
                    </div>
                </div>
                <div class="contact-field">
                    <label for="contact-category">What do you need help with? <span>*</span></label>
                    <select id="contact-category" name="category" required>
                        <option value="" disabled <?= $category === "" ? "selected" : "" ?>>Select a topic</option>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, "UTF-8") ?>" <?= $category === $value ? "selected" : "" ?>>
                                <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="contact-field">
                    <label for="contact-subject">Subject <span>*</span></label>
                    <input type="text" id="contact-subject" name="subject" value="<?= htmlspecialchars($subject, ENT_QUOTES, "UTF-8") ?>" placeholder="Briefly describe your question or issue" maxlength="150" required>
                </div>
                <div class="contact-field">
                    <label for="contact-message">Your message <span>*</span></label>
                    <textarea id="contact-message" name="message" rows="7" maxlength="5000" placeholder="Tell us a little more about what you need help with..." required><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></textarea>
                    <span class="contact-field-hint">Please don't include passwords or other sensitive information.</span>
                </div>
                <button type="submit" class="contact-submit">
                    Send message <span aria-hidden="true">→</span>
                </button>
            </form>
        </section>
        <aside class="contact-sidebar">
            <div class="contact-info-card">
                <div class="contact-info-symbol" aria-hidden="true">✦</div>
                <h2>A little guidance</h2>
                <p>Before sending a message, you might find the answer you're looking for in our Help Center.</p>
                <a href="help.php" class="contact-info-link">
                    <span>Help & FAQ</span>
                    <span aria-hidden="true">→</span>
                </a>
                <a href="rules.php" class="contact-info-link">
                    <span>Community Rules</span>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
            <div class="contact-note-card">
                <h3>✧ A friendly reminder</h3>
                <p>Witchy is a space for curiosity, learning, and respectful conversations. We appreciate you helping us make this community better.</p>
            </div>
        </aside>
    </div>
</section>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
