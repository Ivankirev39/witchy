// =========================
// VIDEO AUTOPLAY
// =========================

const feedVideos = document.querySelectorAll(".post-media-video");

const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.5
};

const videoObserver = new IntersectionObserver((entries) => {

    entries.forEach((entry) => {

        const video = entry.target;

        if (
            entry.isIntersecting &&
            entry.intersectionRatio >= 0.5
        ) {

            video.muted = true;

            feedVideos.forEach((otherVideo) => {

                if (
                    otherVideo !== video &&
                    !otherVideo.paused
                ) {
                    otherVideo.pause();
                }

            });

            const playPromise = video.play();

            if (playPromise !== undefined) {

                playPromise.catch((error) => {
                    console.log(
                        "Video autoplay blocked:",
                        error
                    );
                });

            }

        } else {

            video.pause();

        }

    });

});

feedVideos.forEach((video) => {

    video.muted = true;
    videoObserver.observe(video);

});


// =========================
// COMMENTS TOGGLE
// =========================

document.querySelectorAll(".comments-toggle").forEach((button) => {

    button.addEventListener("click", () => {

        const post = button.closest(".post-card");
        const commentsArea = post.querySelector(".comments-area");

        commentsArea.hidden = !commentsArea.hidden;

    });

});


// =========================
// LIKE
// =========================

document.querySelectorAll(".like-form").forEach((form) => {

    form.addEventListener("submit", async (event) => {

        event.preventDefault();

        const button = form.querySelector(".like-button");
        const heart = form.querySelector(".like-heart");
        const count = form.querySelector(".like-count");

        button.disabled = true;

        try {

            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error("Like request failed.");
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }

            button.classList.toggle(
                "active",
                data.liked
            );

            heart.textContent =
                data.liked ? "♥" : "♡";

            count.textContent =
                data.like_count;

            button.setAttribute(
                "aria-label",
                data.liked
                    ? "Unlike post"
                    : "Like post"
            );

        } catch (error) {

            console.error(
                "Like error:",
                error
            );

        } finally {

            button.disabled = false;

        }

    });

});


// =========================
// SAVE
// =========================

document.querySelectorAll(".save-form").forEach((form) => {

    form.addEventListener("submit", async (event) => {

        event.preventDefault();

        const button = form.querySelector(".save-button");

        button.disabled = true;

        try {

            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error("Save request failed.");
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }

            button.classList.toggle(
                "active",
                data.saved
            );

            button.setAttribute(
                "aria-label",
                data.saved
                    ? "Remove from saved"
                    : "Save post"
            );

            button.setAttribute(
                "title",
                data.saved
                    ? "Saved"
                    : "Save"
            );

        } catch (error) {

            console.error(
                "Save error:",
                error
            );

        } finally {

            button.disabled = false;

        }

    });

});


// =========================
// ADD COMMENT
// =========================

document.querySelectorAll(".comment-form").forEach((form) => {

    form.addEventListener("submit", async (event) => {

        event.preventDefault();

        const post = form.closest(".post-card");

        const input =
            form.querySelector('input[name="comment"]');

        const button =
            form.querySelector('button[type="submit"]');

        const comments =
            post.querySelector(".post-comments");

        const count =
            post.querySelector(".comment-count");

        const commentText = input.value.trim();

        if (commentText === "") {
            return;
        }

        button.disabled = true;

        try {

            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error("Comment request failed.");
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }


            // CREATE COMMENT
            const comment = document.createElement("div");

            comment.className = "comment";


            // USERNAME
            const username = document.createElement("strong");

            username.textContent =
                data.username;


            // COMMENT TEXT
            const text = document.createElement("p");

            text.textContent =
                data.comment;


            // DATE
            const date = document.createElement("small");

            date.textContent =
                data.created_at;


            // ADD ELEMENTS
            comment.appendChild(username);
            comment.appendChild(text);
            comment.appendChild(date);

            comments.appendChild(comment);


            // UPDATE COUNT
            count.textContent =
                data.comment_count;


            // CLEAR INPUT
            input.value = "";

        } catch (error) {

            console.error(
                "Comment error:",
                error
            );

        } finally {

            button.disabled = false;

        }

    });

});