<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

$pageTitle = "Community Rules | Witchy";
$pageCss = "admin_rules.css";

$rules = [];
$rulesError = null;
$actionError = null;
$actionSuccess = null;

// Handle rule management actions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid($_POST["csrf_token"] ?? "")) {
        $actionError = "Your session could not be verified. Please try again.";

    } else {

        $action = $_POST["action"] ?? "";

        try {

            if ($action === "add") {

                $title = trim($_POST["title"] ?? "");
                $description = trim($_POST["description"] ?? "");

                if ($title === "" || $description === "") {
                    throw new RuntimeException(
                        "Please provide both a rule title and description."
                    );
                }

                $stmt = $conn->prepare("
                    INSERT INTO rule (title, description)
                    VALUES (:title, :description)
                ");

                $stmt->execute([
                    ":title" => $title,
                    ":description" => $description
                ]);

                header("Location: rules.php?status=added");
                exit;

            } elseif ($action === "edit") {

                $ruleId = filter_var(
                    $_POST["rule_id"] ?? null,
                    FILTER_VALIDATE_INT
                );

                $title = trim($_POST["title"] ?? "");
                $description = trim($_POST["description"] ?? "");

                if (!$ruleId || $ruleId < 1) {
                    throw new RuntimeException("Invalid rule selected.");
                }

                if ($title === "" || $description === "") {
                    throw new RuntimeException(
                        "Please provide both a rule title and description."
                    );
                }

                $stmt = $conn->prepare("
                    UPDATE rule
                    SET title = :title,
                        description = :description
                    WHERE rule_id = :rule_id
                ");

                $stmt->execute([
                    ":title" => $title,
                    ":description" => $description,
                    ":rule_id" => $ruleId
                ]);

                header("Location: rules.php?status=updated");
                exit;

            } elseif ($action === "delete") {

                $ruleId = filter_var(
                    $_POST["rule_id"] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$ruleId || $ruleId < 1) {
                    throw new RuntimeException("Invalid rule selected.");
                }

                $stmt = $conn->prepare("
                    DELETE FROM rule
                    WHERE rule_id = :rule_id
                ");

                $stmt->execute([
                    ":rule_id" => $ruleId
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException(
                        "The selected rule could not be found."
                    );
                }

                header("Location: rules.php?status=deleted");
                exit;

            } else {
                throw new RuntimeException("Invalid action.");
            }

        } catch (RuntimeException $e) {
            $actionError = $e->getMessage();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $actionError = "The rule could not be saved. Please try again.";
        }
    }
}

// Display success messages after redirect.
$status = $_GET["status"] ?? "";

$successMessages = [
    "added" => "The new community rule has been added.",
    "updated" => "The community rule has been updated.",
    "deleted" => "The community rule has been deleted."
];

if (isset($successMessages[$status])) {
    $actionSuccess = $successMessages[$status];
}

// Load the current rules.
try {
    $stmt = $conn->query("
        SELECT rule_id, title, description
        FROM rule
        ORDER BY rule_id ASC
    ");

    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $rulesError = "The community rules could not be loaded. Please try again later.";
}

$ruleCount = count($rules);

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-rules-page">

    <!-- Page heading -->
    <div class="rules-page-header">
        <div class="rules-heading-text">
            <span class="rules-eyebrow">COMMUNITY MANAGEMENT</span>

            <h2>Community Guidelines</h2>

            <p class="rules-description">
                The principles that help make Witchy a welcoming,
                respectful, and safe space for everyone.
            </p>
        </div>

        <div class="rules-header-decoration" aria-hidden="true">
            &#10022;
        </div>
    </div>


    <!-- Action messages -->
    <?php if ($actionSuccess !== null): ?>
        <div class="rules-message rules-message-success" role="status">
            <?= htmlspecialchars($actionSuccess, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($actionError !== null): ?>
        <div class="rules-message rules-message-error" role="alert">
            <?= htmlspecialchars($actionError, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>


    <!-- Overview -->
    <section class="rules-overview" aria-label="Rules overview">

        <div class="rules-overview-main">
            <div class="rules-overview-icon" aria-hidden="true">
                &#9776;
            </div>

            <div class="rules-overview-count">
                <span class="rules-overview-label">
                    PUBLISHED GUIDELINES
                </span>

                <strong><?= $ruleCount ?></strong>

                <span class="rules-overview-caption">
                    Community rules
                </span>
            </div>
        </div>

        <div class="rules-overview-divider"></div>

        <div class="rules-overview-message">
            <strong>A shared space starts with respect.</strong>

            <p>
                These guidelines apply to every member.
                Together, we create the community we want to be part of.
            </p>
        </div>

    </section>


    <!-- Add rule -->
    <section class="rules-management-section">

        <div class="rules-section-heading">
            <div>
                <h3>Add a new rule</h3>
                <p>Create a guideline that will be displayed to all members.</p>
            </div>
        </div>

        <form method="POST" action="rules.php" class="rules-management-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
            >

            <input type="hidden" name="action" value="add">

            <div class="rules-form-group">
                <label for="new-rule-title">Rule title</label>

                <input
                    type="text"
                    id="new-rule-title"
                    name="title"
                    placeholder="Enter the rule title..."
                    required
                >
            </div>

            <div class="rules-form-group">
                <label for="new-rule-description">Rule description</label>

                <textarea
                    id="new-rule-description"
                    name="description"
                    rows="4"
                    placeholder="Explain the rule..."
                    required
                ></textarea>
            </div>

            <button type="submit" class="rules-add-button">
                + Add rule
            </button>

        </form>

    </section>


    <!-- Rules section heading -->
    <div class="rules-section-heading">
        <div>
            <h3>All community rules</h3>

            <p>
                Manage the guidelines every Witchy member is expected to follow.
            </p>
        </div>

        <span class="rules-section-count">
            <?= $ruleCount ?> rules
        </span>
    </div>


    <!-- Rules -->
    <?php if ($rulesError !== null): ?>

        <div class="rules-message rules-message-error" role="alert">
            <?= htmlspecialchars($rulesError, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php elseif (empty($rules)): ?>

        <div class="rules-empty">
            <div class="rules-empty-icon" aria-hidden="true">
                &#10022;
            </div>

            <h3>No community rules yet</h3>

            <p>
                Add your first community guideline using the form above.
            </p>
        </div>

    <?php else: ?>

        <div class="rules-grid">

            <?php foreach ($rules as $index => $rule): ?>

                <article class="rules-card">

                    <div class="rules-card-top">

                        <span class="rules-number">
                            <?= str_pad(
                                (string) ($index + 1),
                                2,
                                "0",
                                STR_PAD_LEFT
                            ) ?>
                        </span>

                        <span class="rules-card-category">
                            COMMUNITY RULE
                        </span>

                    </div>

                    <div class="rules-card-body">

                        <h4>
                            <?= htmlspecialchars(
                                $rule["title"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h4>

                        <p>
                            <?= nl2br(htmlspecialchars(
                                $rule["description"],
                                ENT_QUOTES,
                                "UTF-8"
                            )) ?>
                        </p>

                    </div>

                    <div class="rules-card-footer">
                        <span class="rules-status-indicator"></span>
                        <span>Community guideline</span>
                    </div>


                    <!-- Rule management buttons -->
                    <div class="rules-card-actions">

                        <button
                            type="button"
                            class="rules-edit-button"
                            onclick="toggleRuleEdit(<?= (int) $rule["rule_id"] ?>)"
                        >
                            Edit
                        </button>

                        <form
                            method="POST"
                            action="rules.php"
                            onsubmit="return confirm('Are you sure you want to delete this rule? This will also remove it from the members’ Rules page.');"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input type="hidden" name="action" value="delete">

                            <input
                                type="hidden"
                                name="rule_id"
                                value="<?= (int) $rule["rule_id"] ?>"
                            >

                            <button type="submit" class="rules-delete-button">
                                Delete
                            </button>
                        </form>

                    </div>


                    <!-- Hidden edit form -->
                    <div
                        class="rules-edit-panel"
                        id="edit-rule-<?= (int) $rule["rule_id"] ?>"
                        hidden
                    >

                        <form method="POST" action="rules.php">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input type="hidden" name="action" value="edit">

                            <input
                                type="hidden"
                                name="rule_id"
                                value="<?= (int) $rule["rule_id"] ?>"
                            >

                            <div class="rules-form-group">
                                <label for="edit-title-<?= (int) $rule["rule_id"] ?>">
                                    Rule title
                                </label>

                                <input
                                    type="text"
                                    id="edit-title-<?= (int) $rule["rule_id"] ?>"
                                    name="title"
                                    value="<?= htmlspecialchars($rule["title"], ENT_QUOTES, "UTF-8") ?>"
                                    required
                                >
                            </div>

                            <div class="rules-form-group">
                                <label for="edit-description-<?= (int) $rule["rule_id"] ?>">
                                    Rule description
                                </label>

                                <textarea
                                    id="edit-description-<?= (int) $rule["rule_id"] ?>"
                                    name="description"
                                    rows="4"
                                    required
                                ><?= htmlspecialchars($rule["description"], ENT_QUOTES, "UTF-8") ?></textarea>
                            </div>

                            <div class="rules-edit-actions">
                                <button type="submit" class="rules-save-button">
                                    Save changes
                                </button>

                                <button
                                    type="button"
                                    class="rules-cancel-button"
                                    onclick="toggleRuleEdit(<?= (int) $rule["rule_id"] ?>)"
                                >
                                    Cancel
                                </button>
                            </div>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Closing message -->
    <section class="rules-footer-note">

        <div class="rules-footer-icon" aria-hidden="true">
            &#10003;
        </div>

        <div class="rules-footer-content">
            <h3>Building a better community together</h3>

            <p>
                Every member plays a part in keeping Witchy welcoming.
                Treat others with kindness, encourage respectful
                discussions, and report behavior that goes against
                these guidelines.
            </p>
        </div>

    </section>

</section>


<script>
function toggleRuleEdit(ruleId) {
    const panel = document.getElementById("edit-rule-" + ruleId);

    if (panel) {
        panel.hidden = !panel.hidden;
    }
}
</script>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
