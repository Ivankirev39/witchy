<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

// Page configuration
$pageTitle = "Community Rules | Witchy";
$pageCss = "rules.css";

$rules = [];
$rulesError = false;

// Retrieve community rules
try {
    $stmt = $conn->query("
        SELECT rule_id, title, description
        FROM rule
        ORDER BY rule_id ASC
    ");

    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Rules loading error: " . $e->getMessage());
    $rulesError = true;
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="rules-page">

    <header class="rules-header">
        <h1>Community Rules</h1>

        <p>
            How we keep Witchy respectful,
            welcoming and safe for everyone.
        </p>
    </header>

    <div class="rules-list">

        <?php if ($rulesError): ?>

            <div class="rules-empty" role="alert">
                <p>
                    We couldn't load the community rules.
                    Please try again later.
                </p>
            </div>

        <?php elseif (empty($rules)): ?>

            <div class="rules-empty">
                <p>
                    No community rules are available yet.
                </p>
            </div>

        <?php else: ?>

            <?php foreach ($rules as $index => $rule): ?>

                <article class="rule-card">

                    <h2 class="rule-title">
                        <?= (int) ($index + 1) ?>.
                        <?= htmlspecialchars(
                            $rule["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h2>

                    <p class="rule-description">
                        <?= nl2br(htmlspecialchars(
                            $rule["description"],
                            ENT_QUOTES,
                            "UTF-8"
                        )) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
