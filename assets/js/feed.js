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

        if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {

            // Make absolutely sure it is muted before autoplay
            video.muted = true;

            // Pause other videos
            feedVideos.forEach((otherVideo) => {
                if (otherVideo !== video && !otherVideo.paused) {
                    otherVideo.pause();
                }
            });

            // Start this video
            const playPromise = video.play();

            if (playPromise !== undefined) {
                playPromise.catch((error) => {
                    console.log("Video autoplay blocked:", error);
                });
            }

        } else {
            video.pause();
        }
    });
}, observerOptions);

feedVideos.forEach((video) => {
    video.muted = true;
    videoObserver.observe(video);
});


// =========================
// OPEN / CLOSE COMMENTS
// =========================

document.querySelectorAll(".comments-toggle").forEach((button) => {
    button.addEventListener("click", () => {
        const post = button.closest(".post-card");
        const commentsArea = post.querySelector(".comments-area");

        commentsArea.hidden = !commentsArea.hidden;
    });
});


// =========================
// LIKE WITHOUT RELOAD
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
                throw new Error("Like request failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error("Like was not updated");
            }

            if (data.liked) {
                button.classList.add("active");
                heart.textContent = "♥";
                button.setAttribute("aria-label", "Unlike post");
            } else {
                button.classList.remove("active");
                heart.textContent = "♡";
                button.setAttribute("aria-label", "Like post");
            }

            count.textContent = data.like_count;

        } catch (error) {
            console.error("Like error:", error);
        } finally {
            button.disabled = false;
        }
    });
});


// =========================
// SAVE WITHOUT RELOAD
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
                throw new Error("Save request failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error("Save was not updated");
            }

            if (data.saved) {
                button.classList.add("active");
                button.setAttribute("aria-label", "Remove from saved");
                button.setAttribute("title", "Saved");
            } else {
                button.classList.remove("active");
                button.setAttribute("aria-label", "Save post");
                button.setAttribute("title", "Save");
            }

        } catch (error) {
            console.error("Save error:", error);
        } finally {
            button.disabled = false;
        }
    });
});


// =========================
// COMMENT WITHOUT RELOAD
// =========================

document.querySelectorAll(".comment-form").forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const post = form.closest(".post-card");
        const input = form.querySelector('input[name="comment"]');
        const button = form.querySelector('button[type="submit"]');
        const comments = post.querySelector(".post-comments");
        const count = post.querySelector(".comment-count");

        if (input.value.trim() === "") {
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
                throw new Error("Comment request failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error("Comment was not added");
            }

            const newComment = document.createElement("div");
            newComment.className = "comment";

            const username = document.createElement("strong");
            username.textContent = data.username;

            const text = document.createElement("p");
            text.textContent = data.comment;

            const date = document.createElement("small");
            date.textContent = data.created_at;

            newComment.appendChild(username);
            newComment.appendChild(text);
            newComment.appendChild(date);

            comments.appendChild(newComment);

            count.textContent = data.comment_count;
            input.value = "";

        } catch (error) {
            console.error("Comment error:", error);
        } finally {
            button.disabled = false;
        }
    });
});

// =========================
// DELETE COMMENT WITHOUT RELOAD
// =========================

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".delete-comment-form");

    if (!form) {
        return;
    }

    event.preventDefault();

    const confirmed = confirm("Are you sure you want to delete this comment?");

    if (!confirmed) {
        return;
    }

    const post = form.closest(".post-card");
    const comment = form.closest(".comment");
    const count = post.querySelector(".comment-count");
    const button = form.querySelector(".delete-comment-button");

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
            throw new Error("Delete comment request failed");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error("Comment could not be deleted");
        }

        // Remove the comment from the page
        comment.remove();

        // Update comment count
        count.textContent = data.comment_count;

    } catch (error) {
        console.error("Delete comment error:", error);
        button.disabled = false;
    }
});