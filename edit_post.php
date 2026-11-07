<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/KeywordFilter.php";

function getSafeEditReturnUrl($url)
{
    if (!is_string($url) || $url === "" || preg_match('/[\r\n\\\\]/', $url)) {
        return null;
    }

    $parts = parse_url($url);

    if (
        $parts === false ||
        isset($parts["scheme"]) ||
        isset($parts["host"]) ||
        isset($parts["user"]) ||
        isset($parts["pass"])
    ) {
        return null;
    }

    $path = $parts["path"] ?? "";

    if (
        $path === "" ||
        $path[0] !== "/" ||
        str_starts_with($path, "//") ||
        in_array(basename($path), ["post.php", "edit_post.php"], true)
    ) {
        return null;
    }

    $returnUrl = $path;

    if (!empty($parts["query"])) {
        $returnUrl .= "?" . $parts["query"];
    }

    if (!empty($parts["fragment"])) {
        $returnUrl .= "#" . $parts["fragment"];
    }

    return $returnUrl;
}

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$postModel = new Post($pdo);

$pageTitle = "Edit post | Witchy";
$pageCss = "edit_post.css";

$userId = (int) $_SESSION["user_id"];
$postId = (int) ($_GET["id"] ?? $_POST["post_id"] ?? 0);

$returnUrl = getSafeEditReturnUrl(
    $_POST["return"] ?? $_GET["return"] ?? ""
);

if ($postId <= 0) {
    header("Location: feed.php");
    exit;
}

$postOwnerId = $postModel->getPostOwnerId($postId);

if ($postOwnerId === null) {
    header("Location: feed.php");
    exit;
}

if ($postOwnerId !== $userId) {
    http_response_code(403);
    exit("You are not allowed to edit this post.");
}

$post = $postModel->getPostById($postId, $userId);

if ($post === null) {
    header("Location: feed.php");
    exit;
}

$title = $post["title"];
$description = $post["description"];
$topic = $post["topic"];
$sources = $post["sources"];
$postMedia = $postModel->getPostMedia($postId);

$errorMessage = "";

$allowedTopics = [
    "spells",
    "altars",
    "tarot",
    "herbs",
    "crystals",
    "books",
    "artwork",
    "occult-studies",
    "others"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $topic = trim($_POST["topic"] ?? "");
    $sources = trim($_POST["sources"] ?? "");

    if ($title === "") {
        $errorMessage = "Please enter a title.";
    } elseif (
        (function_exists("mb_strlen") ? mb_strlen($title) : strlen($title)) > 150
    ) {
        $errorMessage = "The title cannot exceed 150 characters.";
    } elseif (!in_array($topic, $allowedTopics, true)) {
        $errorMessage = "Please select a valid topic.";
    }

    if ($errorMessage === "") {
        $keywordFilter = new KeywordFilter();

        $fieldsToCheck = [
            "Title" => $title,
            "Description" => $description,
            "Sources" => $sources
        ];

        foreach ($fieldsToCheck as $fieldName => $fieldValue) {
            if ($keywordFilter->containsBlockedContent($fieldValue)) {
                $errorMessage =
                    "Your " . strtolower($fieldName) .
                    " contains inappropriate language. Please edit it and try again.";

                break;
            }
        }
    }

    $removeMediaIds = [];

    if ($errorMessage === "" && isset($_POST["remove_media"])) {
        if (!is_array($_POST["remove_media"])) {
            $errorMessage = "Invalid media selection.";
        } else {
            foreach ($_POST["remove_media"] as $mediaId) {
                $validatedId = filter_var($mediaId, FILTER_VALIDATE_INT);

                if ($validatedId === false || $validatedId <= 0) {
                    $errorMessage = "Invalid media selection.";
                    break;
                }

                $removeMediaIds[] = $validatedId;
            }

            $removeMediaIds = array_values(array_unique($removeMediaIds));

            $existingIds = array_map(
                "intval",
                array_column($postMedia, "media_id")
            );

            foreach ($removeMediaIds as $mediaId) {
                if (!in_array($mediaId, $existingIds, true)) {
                    $errorMessage = "Invalid media selection.";
                    break;
                }
            }
        }
    }

    $mediaFiles = [];
    $validatedMedia = [];
    $movedFiles = [];

    if (
        $errorMessage === "" &&
        isset($_FILES["media"]) &&
        is_array($_FILES["media"]["name"])
    ) {
        $fileCount = count($_FILES["media"]["name"]);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES["media"]["error"][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $mediaFiles[] = [
                "name" => $_FILES["media"]["name"][$i],
                "tmp_name" => $_FILES["media"]["tmp_name"][$i],
                "size" => $_FILES["media"]["size"][$i],
                "error" => $_FILES["media"]["error"][$i]
            ];
        }
    }

    $allowedTypes = [
        "image/jpeg" => ["extension" => "jpg", "type" => "image"],
        "image/png" => ["extension" => "png", "type" => "image"],
        "image/gif" => ["extension" => "gif", "type" => "image"],
        "image/webp" => ["extension" => "webp", "type" => "image"],
        "video/mp4" => ["extension" => "mp4", "type" => "video"],
        "video/webm" => ["extension" => "webm", "type" => "video"]
    ];

    $uploadDirectory = __DIR__ . "/uploads/posts/";
    $maxTotalMediaSize = 35 * 1024 * 1024;
    $totalMediaSize = 0;

    if ($errorMessage === "") {
        $remainingMedia = array_filter(
            $postMedia,
            function ($media) use ($removeMediaIds) {
                return !in_array(
                    (int) $media["media_id"],
                    $removeMediaIds,
                    true
                );
            }
        );

        if (count($remainingMedia) + count($mediaFiles) > 5) {
            $errorMessage = "A post can have a maximum of 5 images or videos.";
        }
    }

    if ($errorMessage === "" && count($mediaFiles) > 0) {
        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0755, true) &&
            !is_dir($uploadDirectory)
        ) {
            $errorMessage = "The upload directory could not be created.";
        }
    }

    if ($errorMessage === "" && count($mediaFiles) > 0) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($mediaFiles as $media) {
            if ($media["error"] !== UPLOAD_ERR_OK) {
                $errorMessage = "One of your files could not be uploaded.";
                break;
            }

            if (!is_uploaded_file($media["tmp_name"])) {
                $errorMessage = "An invalid upload was detected.";
                break;
            }

            $fileSize = (int) $media["size"];

            if ($fileSize <= 0) {
                $errorMessage = "Empty files cannot be uploaded.";
                break;
            }

            $totalMediaSize += $fileSize;

            if ($totalMediaSize > $maxTotalMediaSize) {
                $errorMessage = "The total media size cannot exceed 35 MB.";
                break;
            }

            $mimeType = $finfo->file($media["tmp_name"]);

            if (!isset($allowedTypes[$mimeType])) {
                $errorMessage = "One of your files has an unsupported file type.";
                break;
            }

            $typeInfo = $allowedTypes[$mimeType];

            if ($typeInfo["type"] === "image") {
                $imageInfo = getimagesize($media["tmp_name"]);

                if ($imageInfo === false) {
                    $errorMessage = "One of your images is invalid.";
                    break;
                }

                $width = $imageInfo[0];
                $height = $imageInfo[1];

                if (
                    $width < 1 ||
                    $height < 1 ||
                    $width > 6000 ||
                    $height > 6000 ||
                    ($width * $height) > 24000000
                ) {
                    $errorMessage = "One of your images exceeds the allowed dimensions.";
                    break;
                }
            }

            $fileName = bin2hex(random_bytes(16)) . "." . $typeInfo["extension"];

            $validatedMedia[] = [
                "tmp_name" => $media["tmp_name"],
                "file" => $fileName,
                "upload_path" => $uploadDirectory . $fileName,
                "media_type" => $typeInfo["type"],
                "file_size" => $fileSize
            ];
        }
    }

    if ($errorMessage === "") {
        $remainingMedia = array_filter(
            $postMedia,
            function ($media) use ($removeMediaIds) {
                return !in_array(
                    (int) $media["media_id"],
                    $removeMediaIds,
                    true
                );
            }
        );

        $remainingSize = array_sum(
            array_column($remainingMedia, "file_size")
        );

        if ($remainingSize + $totalMediaSize > $maxTotalMediaSize) {
            $errorMessage = "The total media size cannot exceed 35 MB.";
        }
    }

    if ($errorMessage === "") {
        try {
            foreach ($validatedMedia as $media) {
                if (!move_uploaded_file(
                    $media["tmp_name"],
                    $media["upload_path"]
                )) {
                    throw new RuntimeException("A media file could not be saved.");
                }

                $movedFiles[] = $media["upload_path"];
            }

            $mediaRecords = [];

            foreach ($validatedMedia as $media) {
                $mediaRecords[] = [
                    "file" => $media["file"],
                    "media_type" => $media["media_type"],
                    "file_size" => $media["file_size"]
                ];
            }

            $removedFiles = $postModel->updatePostWithMedia(
                $postId,
                $userId,
                $title,
                $description,
                $topic,
                $sources,
                $mediaRecords,
                $removeMediaIds
            );

            if ($removedFiles === null) {
                throw new RuntimeException("Post could not be updated.");
            }

            foreach ($removedFiles as $removedFile) {
                $removedPath = $uploadDirectory . basename($removedFile);

                if (is_file($removedPath)) {
                    unlink($removedPath);
                }
            }

            $redirectUrl = "post.php?id=" . $postId . "&updated=1";

            if ($returnUrl !== null) {
                $redirectUrl .= "&return=" . rawurlencode($returnUrl);
            }

            header("Location: " . $redirectUrl);
            exit;

        } catch (Throwable $e) {
            foreach ($movedFiles as $filePath) {
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }

            error_log("Post update failed: " . $e->getMessage());

            if ($e->getMessage() === "Daily upload limit exceeded.") {
                $errorMessage = "Your daily upload limit has been reached.";
            } elseif ($e->getMessage() === "Total storage limit exceeded.") {
                $errorMessage = "Your total storage limit has been reached.";
            } elseif ($e->getMessage() === "Post media limit exceeded.") {
                $errorMessage = "A post can have a maximum of 5 images or videos.";
            } elseif ($e->getMessage() === "Post media size limit exceeded.") {
                $errorMessage = "The total media size cannot exceed 35 MB.";
            } elseif ($e->getMessage() === "Invalid media selection.") {
                $errorMessage = "One of the selected media files is invalid.";
            } else {
                $errorMessage = "Something went wrong. Please try again.";
            }
        }
    }

    $postMedia = $postModel->getPostMedia($postId);
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="edit-page">

    <header class="edit-header">
        <div>
            <h1>Edit your post</h1>
            <p class="edit-subtitle">Make changes to your post</p>
        </div>
    </header>

    <?php if ($errorMessage !== ""): ?>
        <p class="form-error">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form
        class="edit-form"
        action="edit_post.php?id=<?= $postId ?>"
        method="POST"
        enctype="multipart/form-data">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <input
            type="hidden"
            name="post_id"
            value="<?= $postId ?>"
        >

        <input
            type="hidden"
            name="return"
            value="<?= htmlspecialchars($returnUrl ?? "", ENT_QUOTES, "UTF-8") ?>"
        >

        <section class="edit-card">

            <div class="card-header">
                <h2>Post details</h2>
                <p>Update your title and description.</p>
            </div>

            <div class="form-group">
                <label for="title">Title</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>"
                    required
                >

                <small>Maximum 150 characters.</small>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="7"
                ><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

        </section>

        <section class="edit-card">

            <div class="card-header">
                <h2>Media</h2>
                <p>Remove individual files or add new images and videos.</p>
            </div>

            <?php if ($postMedia !== []): ?>
                <div class="current-media">
                    <h3>Current media</h3>

                    <div class="media-preview">
                        <?php foreach ($postMedia as $media): ?>
                            <?php
                            $mediaPath = "uploads/posts/" . rawurlencode(basename($media["file"]));
                            ?>

                            <div
                                class="current-media-item"
                                data-media-id="<?= (int) $media["media_id"] ?>">

                                <?php if ($media["media_type"] === "image"): ?>
                                    <img
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        alt="Current post media"
                                    >
                                <?php else: ?>
                                    <video controls>
                                        <source
                                            src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        >
                                    </video>
                                <?php endif; ?>

                                <button
                                    type="button"
                                    class="media-remove"
                                    data-remove-existing="<?= (int) $media["media_id"] ?>"
                                    aria-label="Remove this media">
                                    ×
                                </button>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p>This post currently has no media.</p>
            <?php endif; ?>

            <div class="form-group">
                <label for="media">Add media</label>

                <input
                    type="file"
                    id="media"
                    name="media[]"
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                    multiple
                >

                <small>
                    Keep your current media, remove individual files, or add more.
                    Maximum 5 files and 35 MB in total per post.
                </small>
            </div>

            <div id="mediaPreview" class="media-preview"></div>

        </section>

        <section class="edit-card">

            <div class="card-header">
                <h2>Topic</h2>
                <p>Choose the topic that best fits your post.</p>
            </div>

            <div class="form-group">
                <label for="topic">Topic / Category</label>

                <select id="topic" name="topic" required>
                    <option value="" disabled>Select a topic</option>

                    <option value="spells" <?= $topic === "spells" ? "selected" : "" ?>>Spells</option>
                    <option value="altars" <?= $topic === "altars" ? "selected" : "" ?>>Altars</option>
                    <option value="tarot" <?= $topic === "tarot" ? "selected" : "" ?>>Tarot</option>
                    <option value="herbs" <?= $topic === "herbs" ? "selected" : "" ?>>Herbs</option>
                    <option value="crystals" <?= $topic === "crystals" ? "selected" : "" ?>>Crystals</option>
                    <option value="books" <?= $topic === "books" ? "selected" : "" ?>>Books</option>
                    <option value="artwork" <?= $topic === "artwork" ? "selected" : "" ?>>Artwork</option>
                    <option value="occult-studies" <?= $topic === "occult-studies" ? "selected" : "" ?>>Occult Studies</option>
                    <option value="others" <?= $topic === "others" ? "selected" : "" ?>>Others</option>
                </select>
            </div>

        </section>

        <section class="edit-card">

            <div class="card-header">
                <h2>Sources & references</h2>
                <p>Update any sources or references related to your post.</p>
            </div>

            <div class="form-group">
                <label for="sources">Sources / References</label>

                <textarea
                    id="sources"
                    name="sources"
                    rows="4"
                ><?= htmlspecialchars($sources, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

        </section>

        <div class="edit-actions">
            <a
                href="post.php?id=<?= $postId ?><?= $returnUrl !== null ? "&return=" . rawurlencode($returnUrl) : "" ?>"
                class="button button-secondary">
                Cancel
            </a>

            <button type="submit" class="button button-primary">
                Save changes
            </button>
        </div>

    </form>

</section>

<script src="assets/js/upload.js" defer></script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>