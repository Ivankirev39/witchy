<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Create a post | Witchy";


/* ========================================
   PROCESS FORM
   ======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $topic = trim($_POST["topic"] ?? "");
    $sources = trim($_POST["sources"] ?? "");


    /* ========================================
       VALIDATION
       ======================================== */

    if ($title === "") {
        die("Please enter a title.");
    }

    if ($topic === "") {
        die("Please select a topic.");
    }

    if (
        !isset($_FILES["image"]) ||
        $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        die("Please upload an image.");
    }


    /* ========================================
       IMAGE VALIDATION
       ======================================== */

    $image = $_FILES["image"];

    if ($image["error"] !== UPLOAD_ERR_OK) {
        die("There was a problem uploading the image.");
    }


    // Maximum file size: 10 MB
    $maxFileSize = 10 * 1024 * 1024;

    if ($image["size"] > $maxFileSize) {
        die("The image is too large. Maximum size is 10 MB.");
    }


    // Check actual MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($image["tmp_name"]);


    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/gif"  => "gif",
        "image/webp" => "webp"
    ];


    if (!array_key_exists($mimeType, $allowedTypes)) {
        die("Invalid image type. Please upload JPG, PNG, GIF, or WebP.");
    }


    /* ========================================
       CREATE UNIQUE FILE NAME
       ======================================== */

    $extension = $allowedTypes[$mimeType];

    $fileName = bin2hex(random_bytes(16)) . "." . $extension;

    $uploadDirectory = __DIR__ . "/uploads/posts/";

    $uploadPath = $uploadDirectory . $fileName;


    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
    }


    /* ========================================
       MOVE IMAGE
       ======================================== */

    if (!move_uploaded_file($image["tmp_name"], $uploadPath)) {
        die("Failed to save the uploaded image.");
    }


    /* ========================================
       SAVE POST TO DATABASE
       ======================================== */

    $sql = "
        INSERT INTO post
        (
            user_id,
            image,
            title,
            description,
            topic,
            sources
        )
        VALUES
        (
            :user_id,
            :image,
            :title,
            :description,
            :topic,
            :sources
        )
    ";

$stmt = $conn->prepare("
    INSERT INTO post
        (user_id, image, title, description, topic, sources)
    VALUES (?, ?, ?, ?, ?, ?)
");

$userId = (int) $_SESSION["user_id"];

$stmt->bind_param(
    "isssss",
    $userId,
    $fileName,
    $title,
    $description,
    $topic,
    $sources
);

$stmt->execute();
$postId = $conn->insert_id;

    /* ========================================
       REDIRECT
       ======================================== */


    header("Location: feed.php");
    exit;
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
             IMAGE
             ================================= -->

        <section class="create-card">

            <div class="card-header">
                <h2>Image</h2>
                <p>
                    Add an image to your post.
                </p>
            </div>

            <label class="image-upload" for="image">

                <div class="image-upload-icon">
                    +
                </div>

                <div class="image-upload-text">
                    <strong>Upload an image</strong>
                    <span>
                        Choose a JPG, PNG, GIF, or WebP image
                    </span>
                </div>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    required
                >

            </label>

         <div id="imagePreview" hidden>
          <img id="imagePreviewImage" alt="Selected image preview">
          <button
           type="button"
           id="removeImage"
           aria-label="Remove selected image"
           title="Remove image"
            >×</button>
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
                >
                    <option value="" selected disabled>
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
                    <span class="optional">Optional</span>
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


<script src="assets/js/upload.js" defer></script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>