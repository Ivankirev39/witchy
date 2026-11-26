<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/User.php";
require_once __DIR__ . "/models/KeywordFilter.php";
require_once __DIR__ . "/includes/flash.php";

$pageTitle = "Edit Profile | Witchy";
$pageCss = "edit_profile.css";

$user_id = (int) $_SESSION["user_id"];
$error = "";

$userModel = new User($conn);
$user = $userModel->getUserById($user_id);


// ========================================
// ACCOUNT NOT FOUND
// ========================================

if (!$user) {
    http_response_code(404);

    $pageTitle = "Account Not Found | Witchy";

    require_once __DIR__ . "/includes/user_header.php";
    ?>

    <section class="post-not-found-page">
        <div class="post-not-found-card">

            <div class="post-not-found-icon">
                ☾
            </div>

            <h1>Account not found</h1>

            <p>
                We couldn't load your account.
                Please contact support if the problem continues.
            </p>

            <a href="feed.php" class="post-not-found-button">
                Back to feed
            </a>

        </div>
    </section>

    <?php
    require_once __DIR__ . "/includes/user_footer.php";
    exit;
}


// ========================================
// PROFILE UPDATE
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check CSRF token.
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        $error = "Your request could not be verified. Please refresh the page and try again.";
    }


    // ====================================
    // INPUT VALIDATION
    // ====================================

    if ($error === "") {

        $rawUsername = $_POST["username"] ?? null;
        $rawBirthdate = $_POST["birthdate"] ?? null;
        $rawBio = $_POST["bio"] ?? null;

        // Only accept strings from the form.
        $username = is_string($rawUsername)
            ? trim($rawUsername)
            : "";

        $birthdate = is_string($rawBirthdate)
            ? trim($rawBirthdate)
            : "";

        $bio = is_string($rawBio)
            ? trim($rawBio)
            : "";

        $keywordFilter = new KeywordFilter();

        if (
            !is_string($rawUsername) ||
            !is_string($rawBirthdate) ||
            !is_string($rawBio)
        ) {
            $error = "Invalid profile update request. Please try again.";

        } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {

            $error = "Username must be 3-50 characters and only contain letters, numbers or underscores.";

        } elseif (strlen($bio) > 500) {

            $error = "Bio cannot be longer than 500 characters.";

        } elseif (
            $bio !== "" &&
            $keywordFilter->containsBlockedContent($bio)
        ) {

            $error = "Your bio contains inappropriate language. Please edit it and try again.";

        } elseif (
            $birthdate !== "" &&
            (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate) ||
                !checkdate(
                    (int) substr($birthdate, 5, 2),
                    (int) substr($birthdate, 8, 2),
                    (int) substr($birthdate, 0, 4)
                ) ||
                $birthdate > date("Y-m-d")
            )
        ) {

            $error = "Please enter a valid birthday.";
        }
    }


    // ====================================
    // USERNAME AVAILABILITY
    // ====================================

    if ($error === "") {

        try {

            if ($userModel->checkUsername($username, $user_id)) {
                $error = "That username is already taken.";
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = "Something went wrong while checking your username.";
        }
    }


    // ====================================
    // PROFILE AND COVER IMAGE UPLOADS
    // ====================================

    if ($error === "") {

        $profile_image = $user["profile_image"] ?? null;
        $cover_image = $user["cover_image"] ?? null;

        $newFiles = [];

        // Named limits make validation easier to maintain.
        $maxImageSize = 10 * 1024 * 1024;
        $maxDimension = 6000;
        $maxPixels = 24000000;

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        $uploads = [
            "profile_image" => [
                "prefix" => "profile_",
                "label" => "profile picture"
            ],
            "cover_image" => [
                "prefix" => "cover_",
                "label" => "cover image"
            ]
        ];


        foreach ($uploads as $field => $settings) {

            if (!isset($_FILES[$field])) {
                continue;
            }

            $file = $_FILES[$field];
            $label = $settings["label"];


            // Validate uploaded file structure.
            if (
                !is_array($file) ||
                !isset($file["error"]) ||
                !is_int($file["error"])
            ) {

                $error = "Invalid " . $label . " upload.";
                break;
            }


            // No new file selected.
            if ($file["error"] === UPLOAD_ERR_NO_FILE) {
                continue;
            }


            // Check upload errors.
            if ($file["error"] !== UPLOAD_ERR_OK) {

                $error = "Something went wrong while uploading the " . $label . ".";
                break;
            }


            // Validate file information.
            if (
                !is_string($file["tmp_name"] ?? null) ||
                !is_int($file["size"] ?? null) ||
                $file["size"] <= 0
            ) {

                $error = "The selected " . $label . " is invalid.";
                break;
            }


            // Maximum file size: 10 MB.
            if ($file["size"] > $maxImageSize) {

                $error = ucfirst($label) . " cannot be larger than 10 MB.";
                break;
            }


            // Verify the file was uploaded through PHP.
            if (!is_uploaded_file($file["tmp_name"])) {

                $error = "Invalid uploaded file.";
                break;
            }


            // Check the actual MIME type.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file["tmp_name"]);


            if (
                !is_string($mimeType) ||
                !isset($allowedTypes[$mimeType])
            ) {

                $error = "Only JPG, PNG and WebP images are allowed.";
                break;
            }


            // Verify that the file is a valid image.
            $imageInfo = @getimagesize($file["tmp_name"]);

            if ($imageInfo === false) {

                $error = "The uploaded image is not valid.";
                break;
            }


            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];


            // Reject images with excessive dimensions.
            if (
                $width < 1 ||
                $height < 1 ||
                $width > $maxDimension ||
                $height > $maxDimension ||
                $width * $height > $maxPixels
            ) {

                $error = "Image dimensions are too large. Maximum 6000px per side and 24 megapixels.";
                break;
            }


            // Generate a unique filename.
            $filename = $settings["prefix"] . $user_id . "_" .
                bin2hex(random_bytes(8)) . "." .
                $allowedTypes[$mimeType];


            $uploadDirectory = __DIR__ . "/uploads/profiles/";
            $uploadPath = $uploadDirectory . $filename;


            // Create the upload folder if necessary.
            if (
                !is_dir($uploadDirectory) &&
                !mkdir($uploadDirectory, 0755, true) &&
                !is_dir($uploadDirectory)
            ) {

                $error = "Could not create the profile image folder.";
                break;
            }


            // Save the uploaded image.
            if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {

                $error = "Could not save the " . $label . ".";
                break;
            }


            // Remember new files in case the update fails.
            $newFiles[] = $uploadPath;

            ${$field} = "uploads/profiles/" . $filename;
        }


        // ====================================
        // SAVE PROFILE CHANGES
        // ====================================

        if ($error === "") {

            if ($birthdate === "") {
                $birthdate = null;
            }

            try {

                $updated = $userModel->updateProfile(
                    $user_id,
                    $username,
                    $birthdate,
                    $bio,
                    $profile_image,
                    $cover_image
                );


                if (!$updated) {
                    throw new RuntimeException("Profile update failed.");
                }


                // Remove old images only after a successful update.
                $previousImages = [
                    "profile_image" => $user["profile_image"] ?? null,
                    "cover_image" => $user["cover_image"] ?? null
                ];

                $currentImages = [
                    "profile_image" => $profile_image,
                    "cover_image" => $cover_image
                ];


                foreach ($previousImages as $field => $oldFile) {

                    $newFile = $currentImages[$field];

                    // Keep the image if it hasn't changed.
                    if (empty($oldFile) || $oldFile === $newFile) {
                        continue;
                    }


                    // Only allow expected profile-image paths.
                    $expectedPrefix = $field === "profile_image"
                        ? "profile"
                        : "cover";

                    $pattern = '~^uploads/profiles/' .
                        $expectedPrefix . '_' .
                        $user_id .
                        '_[a-f0-9]{16}\.(?:jpg|png|webp)$~';


                    if (!preg_match($pattern, $oldFile)) {

                        error_log("Unexpected old profile image path.");
                        continue;
                    }


                    $oldPath = __DIR__ . "/" . $oldFile;


                    if (is_file($oldPath) && !is_link($oldPath)) {

                        if (!unlink($oldPath)) {
                            error_log("Could not delete old profile image.");
                        }
                    }
                }


                // Update session username.
                $_SESSION["username"] = $username;


                // Store a one-time success message.
                flash_set(
                    "success",
                    "Your profile has been updated successfully!"
                );


                // Redirect after successful update.
                header("Location: profile.php");
                exit;


            } catch (Throwable $e) {

                error_log($e->getMessage());

                if (
                    $e instanceof PDOException &&
                    $e->getCode() === "23000" &&
                    (int) ($e->errorInfo[1] ?? 0) === 1062
                ) {

                    $error = "That username is already taken.";

                } else {

                    $error = "Something went wrong while updating your profile.";
                }
            }
        }


        // Remove newly uploaded files if validation or saving fails.
        if ($error !== "") {

            foreach ($newFiles as $path) {

                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }


    // ====================================
    // PRESERVE FORM INPUT AFTER ERRORS
    // ====================================

    if ($error !== "") {

        // Only copy string values back into the form.
        // This prevents invalid arrays reaching htmlspecialchars().
        foreach (["username", "birthdate", "bio"] as $field) {

            if (is_string($_POST[$field] ?? null)) {
                $user[$field] = $_POST[$field];
            }
        }
    }
}


// ========================================
// PAGE OUTPUT
// ========================================

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="edit-profile-page">

    <header class="edit-profile-heading">

        <div>
            <h1>Edit Profile</h1>
            <p>Update your profile information.</p>
        </div>

        <a href="profile.php" class="edit-profile-back">
            Back to profile
        </a>

    </header>


    <?php if ($error !== ""): ?>

        <div class="edit-profile-message error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php endif; ?>


    <form
        action="edit_profile.php"
        method="POST"
        enctype="multipart/form-data"
        class="edit-profile-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >


        <!-- PROFILE PICTURE -->
        <div class="edit-profile-picture-field">

            <div class="edit-profile-picture-preview">

                <?php if (!empty($user["profile_image"])): ?>

                    <img
                        src="<?= htmlspecialchars($user["profile_image"], ENT_QUOTES, "UTF-8") ?>"
                        alt="Current profile picture"
                    >

                <?php else: ?>

                    <img
                        src="images/default-profile.jpg"
                        alt="Default profile picture"
                    >

                <?php endif; ?>

            </div>


            <div class="edit-profile-picture-controls">

                <label for="profile_image">Profile picture</label>

                <input
                    type="file"
                    id="profile_image"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small>JPG, PNG or WebP. Maximum 10 MB.</small>

            </div>
        </div>


        <!-- COVER IMAGE -->
        <div class="edit-profile-cover-field">

            <div class="edit-profile-cover-preview" id="cover_preview">

                <?php if (!empty($user["cover_image"])): ?>

                    <img
                        id="cover_preview_image"
                        src="<?= htmlspecialchars($user["cover_image"], ENT_QUOTES, "UTF-8") ?>"
                        alt="Current profile cover"
                    >

                <?php else: ?>

                    <span id="cover_preview_placeholder">
                        WITCHY
                    </span>

                <?php endif; ?>

            </div>


            <div class="edit-profile-cover-controls">

                <label for="cover_image">Profile cover</label>

                <input
                    type="file"
                    id="cover_image"
                    name="cover_image"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small>JPG, PNG or WebP. Maximum 10 MB.</small>

            </div>
        </div>


        <!-- USERNAME -->
        <div class="edit-profile-field">

            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                minlength="3"
                maxlength="50"
                pattern="[A-Za-z0-9_]{3,50}"
                title="Use 3-50 letters, numbers or underscores"
                value="<?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                required
            >

        </div>


        <!-- EMAIL (READ-ONLY) -->
        <div class="edit-profile-field">

            <label for="email">Email</label>

            <input
                type="email"
                id="email"
                value="<?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>"
                disabled
            >

            <small>Your email cannot be changed here.</small>

        </div>


        <!-- BIRTHDAY -->
        <div class="edit-profile-field">

            <label for="birthdate">Birthday</label>

            <input
                type="date"
                id="birthdate"
                name="birthdate"
                max="<?= date("Y-m-d") ?>"
                value="<?= htmlspecialchars($user["birthdate"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >

        </div>


        <!-- BIO -->
        <div class="edit-profile-field">

            <label for="bio">Bio</label>

            <textarea
                id="bio"
                name="bio"
                rows="6"
                maxlength="500"
                placeholder="Tell the community a little about yourself..."
            ><?= htmlspecialchars($user["bio"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>

            <small>Maximum 500 characters.</small>

        </div>


        <!-- ACTIONS -->
        <div class="edit-profile-actions">

            <a href="profile.php" class="edit-profile-cancel">
                Cancel
            </a>

            <button
                type="submit"
                class="edit-profile-save"
            >
                Save changes
            </button>

        </div>

    </form>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
