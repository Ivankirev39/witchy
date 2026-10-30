<?php
require_once __DIR__ . "/models/DBConnector.php";

$rules = [];
$rulesError = false;

try {
    $pdoConnector = new \DataAccess\DB\DBConnector();
    $pdo = $pdoConnector->connectToDB();

    $stmt = $pdo->query(
        "SELECT title, description
         FROM rule
         ORDER BY rule_id ASC"
    );

    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $rulesError = true;
}

$pageTitle = "Rules";
require_once __DIR__ . "/includes/user_header.php";
?>

<div class="rules-page">
    <div class="rules-header">
        <h1>Rules and Regulations</h1>
        <h2>How we keep things respectful and welcoming for everyone.</h2>
    </div>

    <div class="rules-list">

        <?php if ($rulesError): ?>

            <p class="rules-empty">
                Rules could not be loaded. Please try again later.
            </p>

        <?php elseif (empty($rules)): ?>

            <p class="rules-empty">
                No rules have been added yet.
            </p>

        <?php else: ?>

            <?php foreach ($rules as $index => $rule): ?>

                <div class="rule-card">

                    <h2 class="rule-title">
                        <?= ($index + 1) . '. ' . htmlspecialchars(
                            $rule['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h2>

                    <p class="rule-description">
                        <?= nl2br(htmlspecialchars(
                            $rule['description'],
                            ENT_QUOTES,
                            'UTF-8'
                        )) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
