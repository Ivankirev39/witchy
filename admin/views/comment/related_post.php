
<section class="admin-content-panel">

    <header class="admin-content-panel-header">
        <h3>Related post</h3>
        <p>The post this comment belongs to.</p>
    </header>

    <?php if (!empty($comment["post_id"])): ?>

        <p>
            <strong>Post title:</strong>
            <?= htmlspecialchars(
                $comment["post_title"] ?? "Untitled",
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </p>

        <p>
            <strong>Post ID:</strong>
            <?= (int) $comment["post_id"] ?>
        </p>

        <?php
        $relatedPostUrl = BASE_URL . "admin/view_post.php?id="
            . (int) $comment["post_id"];

        if ($returnFrom === "user" && $returnUserId !== null) {
            $relatedPostUrl .=
                "&from=user&return_user_id=" . $returnUserId;
        }
        ?>

        <p>
            <a href="<?= htmlspecialchars($relatedPostUrl, ENT_QUOTES, "UTF-8") ?>">
                Review related post &rarr;
            </a>
        </p>

    <?php else: ?>

        <div class="admin-content-empty">
            The original post is no longer available.
        </div>

    <?php endif; ?>

</section>
