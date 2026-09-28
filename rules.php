<?php
require_once __DIR__ . "/config/db.php";


$rules = [];
$result = $conn->query("SELECT title, description FROM rule ORDER BY rule_id ASC");
if ($result) {
    $rules = $result->fetch_all(MYSQLI_ASSOC);
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
        <?php if (empty($rules)): ?>
            <p class="rules-empty">No rules have been added yet.</p>
        <?php else: ?>
            <?php foreach ($rules as $index => $rule): ?>
                <div class="rule-card">
                    <h2 class="rule-title"><?= ($index + 1) . '. ' . htmlspecialchars($rule['title']) ?></h2>
                    <p class="rule-description"><?= nl2br(htmlspecialchars($rule['description'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>