<section class="admin-content-panel">

    <header class="admin-content-panel-header">
        <h3>
            <?= htmlspecialchars($post["title"] ?? "Untitled", ENT_QUOTES, "UTF-8") ?>
        </h3>

        <p>
            Post #<?= (int) $post["post_id"] ?>
            · By
            <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
        </p>
    </header>

    <p>
        <strong>Topic:</strong>
        <?= htmlspecialchars($post["topic"] ?? "Uncategorized", ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>
        <strong>Published:</strong>

        <?php if ($createdDate !== false): ?>
            <time datetime="<?= date("c", $createdDate) ?>">
                <?= date("M j, Y · g:i a", $createdDate) ?>
            </time>
        <?php else: ?>
            Unknown
        <?php endif; ?>
    </p>

    <p>
        <strong>Author:</strong>

        <?php if (!empty($post["user_id"])): ?>
            <a href="/witchy/admin/view_user.php?id=<?= (int) $post["user_id"] ?>">
                <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
            </a>
        <?php else: ?>
            Unknown member
        <?php endif; ?>
    </p>

    <div class="admin-post-description">
        <?= nl2br(htmlspecialchars($post["description"] ?? "", ENT_QUOTES, "UTF-8")) ?>
    </div>

    <?php if (!empty($post["image"])): ?>
        <div class="admin-post-media">
            <h4>Main image</h4>

            <img
                src="/witchy/uploads/posts/<?= rawurlencode(basename($post["image"])) ?>"
                alt="Post image"
                loading="lazy"
            >
        </div>
    <?php endif; ?>

    <?php if (!empty($media)): ?>
        <div class="admin-post-media">
            <h4>Additional media</h4>

            <?php foreach ($media as $item): ?>
                <?php
                $filename = basename($item["file"] ?? "");
                $mediaType = strtolower($item["media_type"] ?? "");
                $mediaPath = "/witchy/uploads/posts/" . rawurlencode($filename);
                ?>

                <?php if ($filename !== "" && $mediaType === "image"): ?>
                    <img
                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                        alt="Additional post image"
                        loading="lazy"
                    >

                <?php elseif ($filename !== "" && $mediaType === "video"): ?>
                    <video controls preload="metadata">
                        <source src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>">
                        Your browser does not support this video.
                    </video>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($post["sources"])): ?>
        <div>
            <h4>Sources</h4>

            <p>
                <?= nl2br(htmlspecialchars($post["sources"], ENT_QUOTES, "UTF-8")) ?>
            </p>
        </div>
    <?php endif; ?>

</section>