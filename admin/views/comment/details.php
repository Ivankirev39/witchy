<section class="admin-content-panel">

    <header class="admin-content-panel-header">
        <h3>Comment #<?= (int) $comment["comment_id"] ?></h3>

        <p>
            By
            <?= htmlspecialchars(
                $comment["username"] ?? "Unknown",
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </p>
    </header>

    <p>
        <strong>Created:</strong>
        <?= htmlspecialchars(
            $comment["created_at"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </p>

    <div class="admin-post-description">
        <?= nl2br(htmlspecialchars(
            $comment["comment"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        )) ?>
    </div>

    <?php if (!empty($comment["media_file"])): ?>

        <?php
        $filename = basename($comment["media_file"]);
        $mediaType = strtolower($comment["media_type"] ?? "");
        $mediaPath = BASE_URL . "uploads/comments/" . rawurlencode($filename);
        ?>

        <div class="admin-post-media">

            <h4>Attached media</h4>

            <?php if ($mediaType === "image"): ?>

                <img
                    src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                    alt="Comment attachment"
                    loading="lazy"
                >

            <?php elseif ($mediaType === "video"): ?>

                <video controls preload="metadata">
                    <source src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>">
                    Your browser does not support this video.
                </video>

            <?php else: ?>

                <p>
                    Attachment:
                    <?= htmlspecialchars($filename, ENT_QUOTES, "UTF-8") ?>
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>
