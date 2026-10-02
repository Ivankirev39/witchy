<?php

// Require authentication so only logged-in users can access this page.
require_once __DIR__ . "/includes/auth.php";

// Connect to the Witchy database.
require_once __DIR__ . "/config/db.php";

// Load the CSRF protection functions used by the form.
require_once __DIR__ . "/includes/csrf.php";


$pageTitle = "Edit Profile | Witchy";
$pageCss = "edit_profile.css";

// Get the logged-in user's ID from the session.
// This ensures users can only edit their own profile.
$user_id = $_SESSION["user_id"];

$error = "";


// ========================================
// GET CURRENT USER
// ========================================

// Get the current profile information from the database.
// profile_image is included so the existing picture can be displayed
// and kept if the user does not upload a new one.
$stmt = $conn->prepare("
    SELECT
        username,
        email,
        birthdate,
        bio,
        profile_image,
        cover_image
    FROM users
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();


// Stop the page if the logged-in user cannot be found.
if (!$user) {
    die("User not found.");
}


// ========================================
// UPDATE PROFILE
// ========================================

// Only process profile changes when the form is submitted with POST.
if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // ========================================
    // CSRF PROTECTION
    // ========================================

    // Compare the submitted security token with the token stored
    // in the user's session. This helps prevent forged form requests.
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $submittedToken
        )
    ) {
        $error = "Invalid request. Please try again.";
    }


    // ========================================
    // GET FORM DATA
    // ========================================

    // Only continue if the CSRF check passed.
    if ($error === "") {

        // trim() removes unnecessary spaces from the beginning
        // and end of the submitted values.
        $username = trim($_POST["username"] ?? "");
        $birthdate = trim($_POST["birthdate"] ?? "");
        $bio = trim($_POST["bio"] ?? "");

        // Keep the existing profile picture by default.
        // It will only change if a valid new picture is uploaded.
        $profile_image = $user["profile_image"] ?? null;


        // ========================================
        // PROFILE PICTURE UPLOAD
        // ========================================

        // Only process the upload if the user selected a file.
        if (
            isset($_FILES["profile_image"]) &&
            $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES["profile_image"];


            // Make sure PHP received the uploaded file successfully.
            if ($file["error"] !== UPLOAD_ERR_OK) {

                $error =
                    "Something went wrong while uploading the profile picture.";


            // Limit profile pictures to 5 MB.
            } elseif ($file["size"] > 5 * 1024 * 1024) {

                $error =
                    "Profile picture cannot be larger than 5 MB.";


            } else {

                // Check the actual MIME type of the temporary file.
                // The original filename and extension are not trusted.
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $mimeType = $finfo->file(
                    $file["tmp_name"]
                );


                // Only these image types are accepted.
                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];


                if (!isset($allowedTypes[$mimeType])) {

                    $error =
                        "Only JPG, PNG and WebP images are allowed.";


                } else {

                    // Get the safe extension based on the verified MIME type.
                    $extension = $allowedTypes[$mimeType];


                    // Generate our own random filename.
                    // This prevents users from controlling filenames
                    // or attempting directory/path manipulation.
                    $filename =
                        "profile_" .
                        $user_id .
                        "_" .
                        bin2hex(random_bytes(8)) .
                        "." .
                        $extension;


                    // Server location where profile pictures are stored.
                    $uploadDirectory =
                        __DIR__ . "/uploads/profiles/";

                    $uploadPath =
                        $uploadDirectory . $filename;


                    // Create the profile upload directory if it does
                    // not already exist.
                    if (!is_dir($uploadDirectory)) {

                        if (
                            !mkdir(
                                $uploadDirectory,
                                0755,
                                true
                            )
                        ) {
                            $error =
                                "Could not create the profile picture folder.";
                        }
                    }


                    // Only try to move the image if no previous
                    // upload error occurred.
                    if ($error === "") {

                        // move_uploaded_file() only moves files that
                        // PHP recognizes as genuine HTTP uploads.
                        if (
                            !move_uploaded_file(
                                $file["tmp_name"],
                                $uploadPath
                            )
                        ) {

                            $error =
                                "Could not save the profile picture.";


                        } else {

                            // Save the relative path in the database
                            // rather than the server's filesystem path.
                            $profile_image =
                                "uploads/profiles/" .
                                $filename;
                        }
                    }
                }
            }
        }

// ========================================
// COVER IMAGE UPLOAD
// ========================================

$cover_image = $user["cover_image"] ?? null;

if (
    $error === "" &&
    isset($_FILES["cover_image"]) &&
    $_FILES["cover_image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    $file = $_FILES["cover_image"];

    if ($file["error"] !== UPLOAD_ERR_OK) {

        $error = "Something went wrong while uploading the cover image.";

    } elseif ($file["size"] > 10 * 1024 * 1024) {

        $error = "Cover image cannot be larger than 10 MB.";

    } else {

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        if (!isset($allowedTypes[$mimeType])) {

            $error = "Only JPG, PNG and WebP images are allowed.";

        } else {

            $extension = $allowedTypes[$mimeType];

            $filename =
                "cover_" .
                $user_id .
                "_" .
                bin2hex(random_bytes(8)) .
                "." .
                $extension;

            $uploadDirectory = __DIR__ . "/uploads/profiles/";

            $uploadPath = $uploadDirectory . $filename;

            if (!is_dir($uploadDirectory)) {

                if (!mkdir($uploadDirectory, 0755, true)) {
                    $error = "Could not create the profile image folder.";
                }
            }

            if ($error === "") {

                if (!move_uploaded_file(
                    $file["tmp_name"],
                    $uploadPath
                )) {

                    $error = "Could not save the cover image.";

                } else {

                    $cover_image =
                        "uploads/profiles/" . $filename;
                }
            }
        }
    }
}


        // ========================================
        // VALIDATE PROFILE INFORMATION
        // ========================================

        // Do not continue if the image upload already caused an error.
        if ($error !== "") {

            // An upload error already exists.

        } elseif ($username === "") {

            $error = "Username cannot be empty.";


        } elseif (strlen($username) > 50) {

            $error =
                "Username cannot be longer than 50 characters.";


        } elseif (strlen($bio) > 500) {

            $error =
                "Bio cannot be longer than 500 characters.";


        } else {


            // ========================================
            // CHECK USERNAME AVAILABILITY
            // ========================================

            // Check whether another user already has this username.
            // The current user's ID is excluded so they can keep
            // their existing username.
            $checkStmt = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
                AND user_id != ?
            ");

            $checkStmt->bind_param(
                "si",
                $username,
                $user_id
            );

            $checkStmt->execute();

            $existingUser = $checkStmt
                ->get_result()
                ->fetch_assoc();


            if ($existingUser) {

                $error =
                    "That username is already taken.";


            } else {


                // ========================================
                // PREPARE OPTIONAL VALUES
                // ========================================

                // Store an empty birthday as NULL instead
                // of an empty string.
                if ($birthdate === "") {
                    $birthdate = null;
                }


                // ========================================
                // UPDATE USER IN DATABASE
                // ========================================

                // Update only the row belonging to the logged-in user.
                // The user ID comes from the session and not from the form.
                $updateStmt = $conn->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        birthdate = ?,
                        bio = ?,
                        profile_image = ?,
                        cover_image = ?
                    WHERE user_id = ?
                ");

                $updateStmt->bind_param(
                    "sssssi",
                    $username,
                    $birthdate,
                    $bio,
                    $profile_image,
                    $cover_image,
                    $user_id
                );


                if ($updateStmt->execute()) {

                    // Keep the username stored in the session synchronized
                    // with the username saved in the database.
                    $_SESSION["username"] = $username;


                    // Redirect after a successful update.
                    // This prevents the form from being submitted again
                    // if the browser page is refreshed.
                    header(
                        "Location: profile.php?updated=1"
                    );

                    exit;


                } else {

                    $error =
                        "Something went wrong while updating your profile.";
                }
            }
        }
    }


    // ========================================
    // KEEP FORM VALUES AFTER AN ERROR
    // ========================================

    // If validation fails, keep the submitted text values so the
    // user does not have to type everything again.
    if ($error !== "") {

        $user["username"] =
            $_POST["username"] ??
            $user["username"];

        $user["birthdate"] =
            $_POST["birthdate"] ??
            $user["birthdate"];

        $user["bio"] =
            $_POST["bio"] ??
            $user["bio"];
    }
}


// Load the shared header/navigation for logged-in users.
require_once __DIR__ . "/includes/user_header.php";

?>


<section class="edit-profile-page">

    <!-- ========================================
         PAGE HEADER
         ======================================== -->

    <header class="edit-profile-heading">

        <div>
            <h1>Edit Profile</h1>

            <p>
                Update your profile information.
            </p>
        </div>

        <a
            href="profile.php"
            class="edit-profile-back"
        >
            Back to profile
        </a>

    </header>


    <!-- ========================================
         ERROR MESSAGE
         Only appears if validation or security
         checks fail.
         ======================================== -->

    <?php if ($error !== ""): ?>

        <div class="edit-profile-message error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <!-- ========================================
         EDIT PROFILE FORM
         multipart/form-data is required because
         this form can upload an image.
         ======================================== -->

    <form
        action="edit_profile.php"
        method="POST"
        enctype="multipart/form-data"
        class="edit-profile-form"
    >


        <!--
            CSRF TOKEN
            Hidden security token that is checked
            when the form is submitted.
        -->

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >


        <!-- ========================================
             PROFILE PICTURE
             ======================================== -->

        <div class="edit-profile-picture-field">


            <!-- Current profile picture / fallback initial -->

            <div class="edit-profile-picture-preview">

                <?php if (!empty($user["profile_image"])): ?>

                    <img
                        src="<?= htmlspecialchars(
                            $user["profile_image"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Current profile picture"
                    >

                <?php else: ?>

                    <div class="edit-profile-picture-fallback">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $user["username"],
                                    0,
                                    1
                                )
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- New profile picture upload -->

            <div class="edit-profile-picture-controls">

                <label for="profile_image">
                    Profile picture
                </label>

                <input
                    type="file"
                    id="profile_image"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small>
                    JPG, PNG or WebP. Maximum 5 MB.
                </small>

            </div>

        </div>

        <div class="edit-profile-cover-field">

    <label for="cover_image">
        Profile cover
    </label>

    <input
        type="file"
        id="cover_image"
        name="cover_image"
        accept="image/jpeg,image/png,image/webp"
    >

    <small>
        JPG, PNG or WebP. Maximum 10 MB.
    </small>

</div>


        <!-- ========================================
             USERNAME
             Must be unique and is limited to
             50 characters.
             ======================================== -->

        <div class="edit-profile-field">

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                value="<?= htmlspecialchars(
                    $user["username"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>


        <!-- ========================================
             EMAIL
             Displayed for reference but cannot
             be changed from this page.
             ======================================== -->

        <div class="edit-profile-field">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                value="<?= htmlspecialchars(
                    $user["email"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                disabled
            >

            <small>
                Your email cannot be changed here.
            </small>

        </div>


        <!-- ========================================
             BIRTHDAY
             Optional profile information.
             ======================================== -->

        <div class="edit-profile-field">

            <label for="birthdate">
                Birthday
            </label>

            <input
                type="date"
                id="birthdate"
                name="birthdate"
                value="<?= htmlspecialchars(
                    $user["birthdate"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>


        <!-- ========================================
             BIO
             Optional and limited to 500 characters.
             ======================================== -->

        <div class="edit-profile-field">

            <label for="bio">
                Bio
            </label>

            <textarea
                id="bio"
                name="bio"
                rows="6"
                maxlength="500"
                placeholder="Tell the community a little about yourself..."
            ><?= htmlspecialchars(
                $user["bio"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ) ?></textarea>

            <small>
                Maximum 500 characters.
            </small>

        </div>


        <!-- ========================================
             FORM ACTIONS
             Cancel returns to the profile.
             Save validates and updates the account.
             ======================================== -->

        <div class="edit-profile-actions">

            <a
                href="profile.php"
                class="edit-profile-cancel"
            >
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

// Load the shared footer for logged-in pages.
require_once __DIR__ . "/includes/user_footer.php";

?>