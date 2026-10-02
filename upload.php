<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Create a post | Witchy";
/* PROCESS FORM */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $topic = trim($_POST["topic"] ?? "");
    $sources = trim($_POST["sources"] ?? "");

    $userId = (int) $_SESSION["user_id"];
    /* BASIC VALIDATION */
    if ($title === "") {
        die("Please enter a title.");
    }
    if ($topic === "") {
        die("Please select a topic.");
    }
    /*  PREPARE MEDIA,  Media is optional */
    $mediaFiles = [];
    if (isset($_FILES["media"])) {
        $fileCount = count($_FILES["media"]["name"]);
        for ($i = 0; $i < $fileCount; $i++) {
            // Ignore empty file inputs
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
    /* MAXIMUM 5 MEDIA FILES*/
    if (count($mediaFiles) > 5) {
        die("You can upload a maximum of 5 images or videos.");
    }
    /* ALLOWED MEDIA TYPES */
    $allowedTypes = [
        "image/jpeg" => [
            "extension" => "jpg",
            "type" => "image"
        ],

        "image/png" => [
            "extension" => "png",
            "type" => "image"
        ],

        "image/gif" => [
            "extension" => "gif",
            "type" => "image"
        ],

        "image/webp" => [
            "extension" => "webp",
            "type" => "image"
        ],

        "video/mp4" => [
            "extension" => "mp4",
            "type" => "video"
        ],

        "video/webm" => [
            "extension" => "webm",
            "type" => "video"
        ]
    ];
    /* UPLOAD DIRECTORY */
    $uploadDirectory = __DIR__ . "/uploads/posts/";
    if (!is_dir($uploadDirectory)) {

        if (!mkdir($uploadDirectory, 0755, true)) {
            die("Could not create the upload directory.");
        }
    }
    /* VALIDATE MEDIA */
    $validatedMedia = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    /* PHP post_max_size is 40 MB. Keep Witchy's own media limit slightly lower. */
    $maxTotalMediaSize = 35 * 1024 * 1024;
    $totalMediaSize = 0;

    foreach ($mediaFiles as $file) {

        /* Check upload error */

        if ($file["error"] !== UPLOAD_ERR_OK) {
            die("There was a problem uploading one of your files.");
        }


        /* Add to total size */

        $totalMediaSize += $file["size"];

        if ($totalMediaSize > $maxTotalMediaSize) {
            die("Your media files are too large. Keep the total under 35 MB.");
        }
        /* Detect actual MIME type */
        $mimeType = $finfo->file($file["tmp_name"]);
        /* Check MIME type */
        if (!isset($allowedTypes[$mimeType])) {
            die(
                "Invalid file type. Allowed formats are JPG, PNG, GIF, WebP, MP4 and WebM."
            );
        }
        /* Get file information */
        $extension = $allowedTypes[$mimeType]["extension"];
        $mediaType = $allowedTypes[$mimeType]["type"];
        /* Create secure unique filename */
        $fileName =
            bin2hex(random_bytes(16))
            . "."
            . $extension;
        $uploadPath = $uploadDirectory . $fileName;
        /* Add validated file */
        $validatedMedia[] = [
            "tmp_name" => $file["tmp_name"],
            "file_name" => $fileName,
            "upload_path" => $uploadPath,
            "media_type" => $mediaType
        ];
    }

    /* DATABASE TRANSACTION */
    $conn->begin_transaction();
    $movedFiles = [];

    try {

        /* ========================================
           CREATE POST
           ======================================== */

        $stmt = $conn->prepare("
            INSERT INTO post
                (
                    user_id,
                    title,
                    description,
                    topic,
                    sources
                )
            VALUES (?, ?, ?, ?, ?)
        ");


        $stmt->bind_param(
            "issss",
            $userId,
            $title,
            $description,
            $topic,
            $sources
        );


        $stmt->execute();


        /* Get new post ID */

        $postId = $conn->insert_id;


        $stmt->close();


        /* ========================================
           SAVE MEDIA
           ======================================== */

        if (!empty($validatedMedia)) {

            $mediaStmt = $conn->prepare("
                INSERT INTO post_media
                    (
                        post_id,
                        file,
                        media_type,
                        sort_order
                    )
                VALUES (?, ?, ?, ?)
            ");


            foreach ($validatedMedia as $index => $media) {

                /* Move file into uploads/posts */

                if (
                    !move_uploaded_file(
                        $media["tmp_name"],
                        $media["upload_path"]
                    )
                ) {

                    throw new Exception(
                        "Failed to save one of the uploaded files."
                    );
                }


                /* Remember file in case rollback is needed */

                $movedFiles[] = $media["upload_path"];


                /* Position of media in post */

                $sortOrder = $index + 1;


                /* Save media in database */

                $mediaStmt->bind_param(
                    "issi",
                    $postId,
                    $media["file_name"],
                    $media["media_type"],
                    $sortOrder
                );


                $mediaStmt->execute();
            }


            $mediaStmt->close();
        }


        /* ========================================
           SUCCESS
           ======================================== */

        $conn->commit();


        /* ========================================
           REDIRECT
           ======================================== */

        header("Location: feed.php");
        exit;


    } catch (Throwable $e) {

        /* Undo database changes */

        $conn->rollback();


        /* Remove files that were already moved */

        foreach ($movedFiles as $movedFile) {

            if (file_exists($movedFile)) {
                unlink($movedFile);
            }
        }


        die(
            "Could not create the post: "
            . htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                "UTF-8"
            )
        );
    }
}


require_once __DIR__ . "/includes/user_header.php";

?>


<section class="create-page">

    <header class="create-header">

        <div>

            <h1>Create a post</h1>

            <p class="create-subtitle">
                Share a little magic
            </p>

        </div>

    </header>


    <form
        class="create-form"
        action="upload.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >


        <!-- ================================
             MEDIA
             ================================= -->

        <section class="create-card">

            <div class="card-header">

                <h2>Media</h2>

                <p>
                    Add up to 5 images and/or videos.
                    Media is optional.
                </p>

            </div>


            <div class="form-group">

                <label for="media">
                    Images / Videos
                    <span class="optional">Optional</span>
                </label>


                <input
                    type="file"
                    id="media"
                    name="media[]"
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                    multiple
                >


                <small>
                    Add up to 5 JPG, PNG, GIF, WebP, MP4 or WebM files.
                    You can add more files after your first selection.
                </small>


                <div
                    id="mediaPreview"
                    class="media-preview"
                ></div>

            </div>

        </section>


        <!-- ================================
             POST DETAILS
             ================================= -->

        <section class="create-card">

            <div class="card-header">

                <h2>Post details</h2>

                <p>
                    Give your post a title and describe what you are sharing.
                </p>

            </div>


            <div class="form-group">

                <label for="title">
                    Title
                </label>


                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    placeholder="Give your post a title"
                    required
                >


                <small>
                    Maximum 150 characters.
                </small>

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="7"
                    placeholder="Tell the community about your post..."
                ></textarea>
            </div>
        </section>
        <!-- ================================
             TOPIC
             ================================= -->
        <section class="create-card">
            <div class="card-header">
                <h2>Topic</h2>
                <p>
                    Choose the topic that best fits your post.
                </p>
            </div>
            <div class="form-group">
                <label for="topic">
                    Topic / Category
                </label>
                <select
                    id="topic"
                    name="topic"
                    required
                >
                    <option
                        value=""
                        selected
                        disabled
                    >
                        Select a topic
                    </option>
                    <option value="spells">
                        Spells
                    </option>
                    <option value="altars">
                        Altars
                    </option>
                    <option value="tarot">
                        Tarot
                    </option>
                    <option value="herbs">
                        Herbs
                    </option>
                    <option value="crystals">
                        Crystals
                    </option>
                    <option value="books">
                        Books
                    </option>
                    <option value="artwork">
                        Artwork
                    </option>
                    <option value="occult-studies">
                        Occult Studies
                    </option>
                    <option value="others">
                        Others
                    </option>
                </select>
            </div>
        </section>
        <!-- ================================
             SOURCES
             ================================= -->
        <section class="create-card">
            <div class="card-header">
                <h2>Sources & references</h2>
                <p>
                    Add any sources or references related to your post.
                </p>
            </div>
            <div class="form-group">
                <label for="sources">
                    Sources / References
                    <span class="optional">
                        Optional
                    </span>
                </label>
                <textarea
                    id="sources"
                    name="sources"
                    rows="4"
                    placeholder="Add books, websites, authors, or other references..."
                ></textarea>
            </div>
        </section>
        <!-- ================================
             ACTIONS
             ================================= -->
        <div class="create-actions">
            <a
                href="index.php"
                class="button button-secondary"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="button button-primary"
            >
                Publish post
            </button>
        </div>
    </form>
</section>
<script
    src="assets/js/upload.js"
    defer
></script>


<?php
require_once __DIR__ . "/includes/user_footer.php";
?>