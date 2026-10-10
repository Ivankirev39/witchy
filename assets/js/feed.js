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
        const mediaInput = form.querySelector(".comment-media-input");

        if (input.value.trim() === "" && !mediaInput.files.length) {
            return;
        }
        if (mediaInput.files.length && mediaInput.files[0].size > 5 * 1024 * 1024) {
            alert("Image must be smaller than 5 MB.");
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
            const content = document.createElement("div");
            content.className = "feed-comment-content";
            if (data.comment) {
                const text = document.createElement("p");
                text.textContent = data.comment;
                content.appendChild(text);
            }
            if (data.media_url) {
                const image = document.createElement("img");
                image.className = "comment-media";
                image.src = data.media_url;
                image.alt = "Comment attachment";
                image.loading = "lazy";
                content.appendChild(image);
            }
            const deleteForm = document.createElement("form");
            deleteForm.action = "comment_delete.php";
            deleteForm.method = "POST";
            deleteForm.className = "delete-comment-form";
            const commentId = document.createElement("input");
            commentId.type = "hidden";
            commentId.name = "comment_id";
            commentId.value = data.comment_id;
            const postId = document.createElement("input");
            postId.type = "hidden";
            postId.name = "post_id";
            postId.value = form.querySelector('input[name="post_id"]').value;
            const returnTo = document.createElement("input");
            returnTo.type = "hidden";
            returnTo.name = "return_to";
            returnTo.value = "feed";
            const deleteButton = document.createElement("button");
            deleteButton.type = "submit";
            deleteButton.className = "delete-comment-button";
            deleteButton.textContent = "Delete";
            deleteForm.append(commentId, postId, returnTo, deleteButton);
            content.appendChild(deleteForm);
            const date = document.createElement("small");
            date.textContent = data.created_at;
            newComment.appendChild(username);
            newComment.appendChild(content);
            newComment.appendChild(date);
            comments.appendChild(newComment);
            count.textContent = data.comment_count;
            form.reset();

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

// COMMENT IMAGE PREVIEW
document.querySelectorAll(".comment-form").forEach((form) => {
    const input = form.querySelector(".comment-media-input");
    const preview = form.querySelector(".comment-media-preview");
    const image = preview.querySelector(".comment-preview-image");
    const remove = preview.querySelector(".comment-preview-remove");
    let previewUrl = null;
    function clearPreview() {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        input.value = "";
        image.removeAttribute("src");
        preview.hidden = true;
    }
    input.addEventListener("change", () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        const file = input.files[0];
        if (!file) {
            clearPreview();
            return;
        }
        if (!["image/jpeg", "image/png", "image/webp", "image/gif"].includes(file.type) || file.size > 5 * 1024 * 1024) {
            alert("Choose a JPG, PNG, WEBP or GIF smaller than 5 MB.");
            clearPreview();
            return;
        }
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        preview.hidden = false;
    });
    remove.addEventListener("click", clearPreview);
    form.addEventListener("reset", () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        image.removeAttribute("src");
        preview.hidden = true;
    });
});

// COMMENT IMAGE LIGHTBOX
const commentLightbox = document.createElement("div");
commentLightbox.className = "comment-image-lightbox";
commentLightbox.hidden = true;
commentLightbox.innerHTML = '<button type="button" class="comment-image-lightbox-close" aria-label="Close image">×</button><img alt="Enlarged comment image">';
document.body.appendChild(commentLightbox);
const lightboxImage = commentLightbox.querySelector("img");
let previousLightboxFocus = null;
function closeCommentLightbox() {
    commentLightbox.hidden = true;
    lightboxImage.removeAttribute("src");
    document.body.style.overflow = "";
    if (previousLightboxFocus) previousLightboxFocus.focus();
    previousLightboxFocus = null;
}
document.addEventListener("click", (event) => {
    const image = event.target.closest(".comment-preview-image, .feed-comment-content .comment-media");
    if (!image) return;
    event.preventDefault();
    previousLightboxFocus = document.activeElement;
    lightboxImage.src = image.currentSrc || image.src;
    commentLightbox.hidden = false;
    document.body.style.overflow = "hidden";
    commentLightbox.querySelector(".comment-image-lightbox-close").focus();
});
commentLightbox.addEventListener("click", closeCommentLightbox);
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !commentLightbox.hidden) closeCommentLightbox();
});