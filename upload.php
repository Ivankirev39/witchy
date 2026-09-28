<?php
require_once __DIR__ . "/includes/auth.php";

$pageTitle = "Create a post | Witchy";
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


<?php
require_once __DIR__ . "/includes/user_footer.php";
?>