<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Home | Witchy";

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="feed-page">

    <h1>Home</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?>.
    </p>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>