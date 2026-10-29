<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Community Rules | Witchy";
$pageCss = "admin_rules.css";

$rules = [];
$rulesError = null;

try {
    $stmt = $conn->query(
        "SELECT rule_id, title, description
         FROM rule
         ORDER BY rule_id ASC"
    );

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

    <!-- Rules section heading -->
    <div class="rules-section-heading">
        <div>
            <h3>All community rules</h3>

            <p>
                The guidelines every Witchy member is expected to follow.
            </p>
        </div>

        <span class="rules-section-count">
            <?= $ruleCount ?> rules
        </span>
    </div>

    <!-- Rules -->
    <?php if ($rulesError !== null): ?>

        <div class="rules-message rules-message-error" role="alert">
            <?= htmlspecialchars(
                $rulesError,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </div>

    <?php elseif (empty($rules)): ?>

        <div class="rules-empty">
            <div class="rules-empty-icon" aria-hidden="true">
                &#10022;
            </div>

            <h3>No community rules yet</h3>

            <p>
                Community guidelines will appear here once they have
                been added to the database.
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
                            <?= htmlspecialchars(
                                $rule["description"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                    </div>

                    <div class="rules-card-footer">
                        <span class="rules-status-indicator"></span>

                        <span>Community guideline</span>
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

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
