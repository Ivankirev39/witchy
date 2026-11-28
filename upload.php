<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/UploadQuota.php";
require_once __DIR__ . "/models/KeywordFilter.php";
require_once __DIR__ . "/models/ImageConverter.php";

// Only errors caused by the user's input should be displayed in detail.
// Unexpected server/database errors are logged and shown as a generic message.
final class UploadFormValidationException extends RuntimeException {}

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();
$uploadQuota = new UploadQuota($pdo);
$postModel = new Post($pdo);

$pageTitle = "Create a post | Witchy";
$pageCss = "upload.css";

// Named limits make the validation easier to understand and maintain.
$maxMediaFiles = 5;
$maxTotalMediaSize = 35 * 1024 * 1024; // 35 MB, in bytes
$maxImageSide = 6000;                 // Pixels
$maxImagePixels = 24_000_000;        // 24 megapixels

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

// Variables are also used by the form to keep the user's input after an error.
$errorMessage = "";
$title = "";
$description = "";
$topic = "";
$sources = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = is_string($_POST["title"] ?? null) ? trim($_POST["title"]) : "";
    $description = is_string($_POST["description"] ?? null) ? trim($_POST["description"]) : "";
    $topic = is_string($_POST["topic"] ?? null) ? trim($_POST["topic"]) : "";
    $sources = is_string($_POST["sources"] ?? null) ? trim($_POST["sources"]) : "";

    $userId = (int) $_SESSION["user_id"];
    $movedFiles = [];

    try {
        // CSRF protection stays in place, but the error is shown in the form.
        if (!csrf_valid($_POST["csrf_token"] ?? null)) {
            http_response_code(403);
            throw new UploadFormValidationException(
                "Your session has expired or the request is invalid. Refresh the page and try again."
            );
        }

        if ($title === "") {
            throw new UploadFormValidationException("Please enter a title.");
        }

        $titleLength = function_exists("mb_strlen")
            ? mb_strlen($title, "UTF-8")
            : strlen($title);

        if ($titleLength > 150) {
            throw new UploadFormValidationException("Your title must be 150 characters or fewer.");
        }

        if (!in_array($topic, $allowedTopics, true)) {
            throw new UploadFormValidationException("Please select a valid topic.");
        }

        $keywordFilter = new KeywordFilter();
        $fieldsToCheck = [
            "Title" => $title,
            "Description" => $description,
            "Sources" => $sources
        ];

        foreach ($fieldsToCheck as $fieldName => $fieldValue) {
            if ($keywordFilter->containsBlockedContent($fieldValue)) {
                throw new UploadFormValidationException(
                    "Your " . strtolower($fieldName) .
                        " contains inappropriate language. Please edit it and try again."
                );
            }
        }

        // Turn PHP's multiple-file upload arrays into a simple list of files.
        $mediaFiles = [];

        if (isset($_FILES["media"])) {
            $uploads = $_FILES["media"];

            if (
                !isset($uploads["name"], $uploads["tmp_name"], $uploads["size"], $uploads["error"]) ||
                !is_array($uploads["name"]) ||
                !is_array($uploads["tmp_name"]) ||
                !is_array($uploads["size"]) ||
                !is_array($uploads["error"])
            ) {
                throw new UploadFormValidationException(
                    "The uploaded files could not be read. Please select them again."
                );
            }

            foreach ($uploads["name"] as $index => $name) {
                $uploadError = $uploads["error"][$index] ?? null;

                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $tmpName = $uploads["tmp_name"][$index] ?? null;
                $fileSize = $uploads["size"][$index] ?? null;

                if (!is_int($uploadError) || !is_string($tmpName) || !is_int($fileSize)) {
                    throw new UploadFormValidationException(
                        "One of your files is invalid. Please select it again."
                    );
                }

                $mediaFiles[] = [
                    "tmp_name" => $tmpName,
                    "size" => $fileSize,
                    "error" => $uploadError
                ];
            }
        }

        if (count($mediaFiles) > $maxMediaFiles) {
            throw new UploadFormValidationException(
                "You can upload a maximum of {$maxMediaFiles} images or videos."
            );
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
        $validatedMedia = [];
        $totalMediaSize = 0;
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($mediaFiles as $file) {
            if ($file["error"] !== UPLOAD_ERR_OK) {
                if (in_array($file["error"], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    throw new UploadFormValidationException(
                        "One of your files exceeds the server's upload size limit."
                    );
                }

                if ($file["error"] === UPLOAD_ERR_PARTIAL) {
                    throw new UploadFormValidationException(
                        "One of your files was only partially uploaded. Please try again."
                    );
                }

                throw new UploadFormValidationException(
                    "There was a problem uploading one of your files. Please try again."
                );
            }

            if (!is_uploaded_file($file["tmp_name"])) {
                throw new UploadFormValidationException("Invalid uploaded file.");
            }

            if ($file["size"] <= 0) {
                throw new UploadFormValidationException("One of your files is empty or invalid.");
            }

            $totalMediaSize += $file["size"];
            if ($totalMediaSize > $maxTotalMediaSize) {
                throw new UploadFormValidationException(
                    "Your media files are too large. Keep the total under 35 MB."
                );
            }

            // Check actual file content, not the MIME type supplied by the browser.
            $mimeType = $finfo->file($file["tmp_name"]);
            if (!is_string($mimeType) || !isset($allowedTypes[$mimeType])) {
                throw new UploadFormValidationException(
                    "Invalid file type. Allowed formats are JPG, PNG, GIF, WebP, MP4 and WebM."
                );
            }

            $extension = $allowedTypes[$mimeType]["extension"];
            $mediaType = $allowedTypes[$mimeType]["type"];

            if ($mediaType === "image") {
                $imageInfo = @getimagesize($file["tmp_name"]);

                if ($imageInfo === false) {
                    throw new UploadFormValidationException("Invalid image.");
                }

                $width = (int) $imageInfo[0];
                $height = (int) $imageInfo[1];

                if (
                    $width < 1 ||
                    $height < 1 ||
                    $width > $maxImageSide ||
                    $height > $maxImageSide ||
                    $width * $height > $maxImagePixels
                ) {
                    throw new UploadFormValidationException(
                        "Image dimensions are too large. Maximum 6000px per side and 24 megapixels."
                    );
                }
            }

            $fileName = bin2hex(random_bytes(16)) . "." . $extension;

            $validatedMedia[] = [
                "tmp_name" => $file["tmp_name"],
                "file_name" => $fileName,
                "upload_path" => $uploadDirectory . $fileName,
                "media_type" => $mediaType,
                "file_size" => $file["size"]
            ];
        }


        // Create the directory before converting images or moving videos.
        if ($validatedMedia !== [] && !is_dir($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException("Could not create the upload directory.");
            }
        }

        // Convert validated images. Videos remain unchanged.
        $converter = new ImageConverter();

        foreach ($validatedMedia as $index => $media) {
            if ($media["media_type"] !== "image") {
                continue;
            }

            $converted = $converter->convert(
                $media["tmp_name"],
                $uploadDirectory
            );

            $validatedMedia[$index]["file_name"] = $converted["file"];
            $validatedMedia[$index]["upload_path"] =
                $uploadDirectory . $converted["file"];
            $validatedMedia[$index]["file_size"] = $converted["file_size"];

            // The existing catch block will delete this if anything later fails.
            $movedFiles[] = $validatedMedia[$index]["upload_path"];
        }

        $newFileSize = array_sum(array_column($validatedMedia, "file_size"));

        if ($newFileSize > $maxTotalMediaSize) {
            throw new UploadFormValidationException(
                "Your media files are too large. Keep the total under 35 MB."
            );
        }

        if (!$uploadQuota->canUpload($userId, $newFileSize)) {
            throw new UploadFormValidationException(
                "Your upload quota has been reached. Please try again later."
            );
        }

        $mediaRecords = [];

        foreach ($validatedMedia as $media) {
            if ($media["media_type"] === "video") {
                if (!move_uploaded_file($media["tmp_name"], $media["upload_path"])) {
                    throw new RuntimeException("Failed to save one of the uploaded files.");
                }

                $movedFiles[] = $media["upload_path"];
            }

            $mediaRecords[] = [
                "file" => $media["file_name"],
                "media_type" => $media["media_type"],
                "file_size" => $media["file_size"]
            ];
        }

        $postModel->createPostWithMedia(
            $userId,
            $title,
            $description,
            $topic,
            $sources,
            $mediaRecords
        );

        header("Location: feed.php");
        exit;
    } catch (Throwable $e) {
        // Remove files already moved if a later step failed.
        foreach ($movedFiles as $movedFile) {
            if (is_file($movedFile)) {
                unlink($movedFile);
            }
        }

        if ($e instanceof UploadFormValidationException) {
            if (http_response_code() !== 403) {
                http_response_code(400);
            }

            $errorMessage = $e->getMessage();
        } elseif (
            $e->getMessage() ===
            "Image requires too much memory to process. Try uploading a smaller image."
        ) {
            http_response_code(400);
            $errorMessage = "This image requires too much memory to process. Try uploading a smaller image.";
        } else {
            error_log("Post creation failed: " . $e->getMessage());
            http_response_code(500);
            $errorMessage = "Could not create the post. Please try again later.";
        }
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="create-page">
    <header class="create-header">
        <div>
            <h1>Create a post</h1>
            <p class="create-subtitle">Share a little magic</p>
        </div>
    </header>

    <?php if ($errorMessage !== ""): ?>
        <div class="create-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form class="create-form" action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

        <section class="create-card">
            <div class="card-header">
                <h2>Media</h2>
                <p>Add up to 5 images and/or videos. Media is optional.</p>
            </div>

            <div class="form-group">
                <label for="media">
                    Images / Videos
                    <span class="optional">Optional</span>
                </label>
                <input type="file" id="media" name="media[]" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm" multiple>
                <small>Add up to 5 JPG, PNG, GIF, WebP, MP4 or WebM files. You can add more files after your first selection.</small>
                <div id="mediaPreview" class="media-preview"></div>
            </div>
        </section>

        <section class="create-card">
            <div class="card-header">
                <h2>Post details</h2>
                <p>Give your post a title and describe what you are sharing.</p>
            </div>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" maxlength="150" placeholder="Give your post a title" value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>" required>
                <small>Maximum 150 characters.</small>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="7" placeholder="Tell the community about your post..."><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>
        </section>

        <section class="create-card">
            <div class="card-header">
                <h2>Topic</h2>
                <p>Choose the topic that best fits your post.</p>
            </div>

            <div class="form-group">
                <label for="topic">Topic / Category</label>
                <select id="topic" name="topic" required>
                    <option value="" disabled <?= $topic === "" ? "selected" : "" ?>>Select a topic</option>
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

        <section class="create-card">
            <div class="card-header">
                <h2>Sources &amp; references</h2>
                <p>Add any sources or references related to your post.</p>
            </div>

            <div class="form-group">
                <label for="sources">
                    Sources / References
                    <span class="optional">Optional</span>
                </label>
                <textarea id="sources" name="sources" rows="4" placeholder="Add books, websites, authors, or other references..."><?= htmlspecialchars($sources, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>
        </section>

        <div class="create-actions">
            <a href="index.php" class="button button-secondary">Cancel</a>
            <button type="submit" class="button button-primary">Publish post</button>
        </div>
    </form>
</section>

<script src="assets/js/upload.js" defer></script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>