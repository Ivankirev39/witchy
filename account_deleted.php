<?php
require_once __DIR__ . "/includes/session.php";

if (empty($_SESSION["account_deleted_message"])) {
    header("Location: index.php");
    exit;
}

unset($_SESSION["account_deleted_message"]);

$pageTitle = "Until Next Time | Witchy";
$pageCss = "account_deleted.css";

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="goodbye-page">
    <section class="goodbye-card">
        <div class="goodbye-symbol" aria-hidden="true">✦</div>

        <h1>Until our paths cross again</h1>

        <p class="goodbye-subtitle">
            Your Witchy account has been deleted.
        </p>

        <p>
            Thank you for being part of our little corner of the universe.
            We hope Witchy brought you inspiration, curiosity,
            and a sense of belonging.
        </p>

        <p class="goodbye-wish">
            May your next chapter be full of magic. ♡
        </p>

        <p class="goodbye-redirect" role="status">
            Returning to the homepage in
            <span id="goodbye-countdown">6</span> seconds...
        </p>

        <a href="index.php" class="goodbye-button">
            Return to Witchy
        </a>
    </section>
</main>

<script>
    let seconds = 6;
    const countdown = document.getElementById("goodbye-countdown");

    const timer = setInterval(() => {
        seconds--;
        countdown.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(timer);
            window.location.replace("index.php");
        }
    }, 1000);
</script>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>
