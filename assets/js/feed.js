// VIDEO AUTOPLAY
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

// OPEN / CLOSE COMMENTS
document.querySelectorAll(".comments-toggle").forEach((button) => {
    button.addEventListener("click", () => {
        const post = button.closest(".post-card");
        const commentsArea = post.querySelector(".comments-area");

        commentsArea.hidden = !commentsArea.hidden;
    });
});

// LIKE WITHOUT RELOAD
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

// SAVE WITHOUT RELOAD
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

// COMMENT WITHOUT RELOAD
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
            const csrfToken = document.createElement("input");
            csrfToken.type = "hidden";
            csrfToken.name = "csrf_token";
            csrfToken.value = form.querySelector('input[name="csrf_token"]').value;
            const deleteButton = document.createElement("button");
            deleteButton.type = "submit";
            deleteButton.className = "delete-comment-button";
            deleteButton.textContent = "Delete";
            deleteForm.append(commentId, postId, returnTo, csrfToken, deleteButton);
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

// DELETE COMMENT WITHOUT RELOAD
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

// FOLLOW USER WITHOUT RELOAD
document.querySelectorAll(".feed-follow-form").forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const userId = form.querySelector('input[name="user_id"]').value;
        const matchingForms = Array.from(
            document.querySelectorAll(".feed-follow-form")
        ).filter((otherForm) => {
            return otherForm.querySelector('input[name="user_id"]').value === userId;
        });
        const matchingButtons = matchingForms.map((matchingForm) => {
            return matchingForm.querySelector(".feed-follow-button");
        });

        matchingButtons.forEach((button) => {
            button.disabled = true;
        });

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error("Follow request failed");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error("Follow state was not updated");
            }

            matchingButtons.forEach((button) => {
                button.classList.toggle("active", data.following);
                button.textContent = data.following ? "Following" : "Follow";
            });
        } catch (error) {
            console.error("Follow error:", error);
        } finally {
            matchingButtons.forEach((button) => {
                button.disabled = false;
            });
        }
    });
});


// LOAD MORE COMMENTS — FEED
document.querySelectorAll(".post-card .load-more-comments-button").forEach(button => {
    button.addEventListener("click", async () => {
        const post = button.closest(".post-card");
        const commentsList = post.querySelector(".post-comments");

        const postId = button.dataset.postId;
        const offset = Number(button.dataset.offset);

        button.disabled = true;
        button.textContent = "Loading...";

        try {
            const response = await fetch(
                `load_more_comments.php?post_id=${encodeURIComponent(postId)}&offset=${offset}`
            );

            if (!response.ok) {
                throw new Error("Failed to load comments.");
            }

            const data = await response.json();

            data.comments.forEach(comment => {
                const wrapper = document.createElement("div");
                wrapper.className = "comment";

                const username = document.createElement("strong");
                const authorLink = document.createElement("a");
                authorLink.href = `profile.php?id=${comment.user_id}`;
                authorLink.className = "comment-author-link";
                authorLink.textContent = comment.username;
                username.appendChild(authorLink);

                const content = document.createElement("div");
                content.className = "feed-comment-content";

                if (comment.comment) {
                    const text = document.createElement("p");
                    text.textContent = comment.comment;
                    content.appendChild(text);
                }

                if (comment.media_file) {
                    const imageLink = document.createElement("a");
                    const image = document.createElement("img");

                    const imageUrl = `uploads/comments/${encodeURIComponent(comment.media_file)}`;

                    imageLink.href = imageUrl;
                    imageLink.className = "comment-media-link";
                    imageLink.setAttribute("aria-label", "Open comment image");

                    image.src = imageUrl;
                    image.className = "comment-media";
                    image.alt = "Comment attachment";
                    image.loading = "lazy";

                    imageLink.appendChild(image);
                    content.appendChild(imageLink);
                }

                // Delete button only for comments owned by current user
                const currentUserId = Number(
                    document.querySelector(".feed-page")?.dataset.userId
                );

                if (Number(comment.user_id) === currentUserId) {
                    const form = document.createElement("form");
                    form.action = "comment_delete.php";
                    form.method = "POST";
                    form.className = "delete-comment-form";

                    const fields = {
                        comment_id: comment.comment_id,
                        post_id: postId,
                        return_to: "feed",
                        csrf_token: post.querySelector(
                            '.comment-form input[name="csrf_token"]'
                        ).value
                    };

                    Object.entries(fields).forEach(([name, value]) => {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    });

                    const deleteButton = document.createElement("button");
                    deleteButton.type = "submit";
                    deleteButton.className = "delete-comment-button";
                    deleteButton.textContent = "Delete";

                    form.appendChild(deleteButton);
                    content.appendChild(form);
                }
                if (Number(comment.user_id) !== currentUserId) {
                    const reportButton = document.createElement("button");
                    reportButton.type = "button";
                    reportButton.className = "report-button";
                    reportButton.textContent = "Report";
                    reportButton.dataset.reportType = "comment";
                    reportButton.dataset.targetId = comment.comment_id;
                    content.appendChild(reportButton);
                }
                const date = document.createElement("small");
                date.textContent = comment.created_at;

                wrapper.append(username, content, date);
                commentsList.appendChild(wrapper);
            });

            button.dataset.offset = offset + data.comments.length;

            if (!data.hasMore) {
                button.remove();
            } else {
                button.textContent = "Show more comments";
                button.disabled = false;
            }

        } catch (error) {
            console.error("Load more comments error:", error);
            button.textContent = "Try again";
            button.disabled = false;
        }
    });
});
// REPORT FUNCTION MODAL
(() => {
    const modal = document.getElementById("report-modal");
    const form = document.getElementById("report-form");

    if (!modal || !form) return;

    const title = document.getElementById("report-modal-title");
    const targetInput = document.getElementById("report-target-id");
    const typeInput = document.getElementById("report-type");
    const reasonInput = document.getElementById("report-reason");
    const detailsInput = document.getElementById("report-details");
    const message = document.getElementById("report-message");
    const submitButton = form.querySelector('[type="submit"]');

    function openReportModal(type, targetId) {
        form.reset();

        typeInput.value = type;
        targetInput.value = targetId;

        title.textContent = type === "comment"
            ? "Report comment"
            : "Report user";

        message.hidden = true;
        message.textContent = "";
        message.className = "report-message";

        submitButton.disabled = false;
        submitButton.textContent = "Submit report";

        form.querySelectorAll("input, select, textarea")
            .forEach(input => {
                input.disabled = false;
            });

        modal.hidden = false;
        reasonInput.focus();
    }

    function closeReportModal() {
        modal.hidden = true;
    }

    // OPEN REPORT MODAL
    document.addEventListener("click", (event) => {
        const button = event.target.closest(".report-button");

        if (!button) return;

        openReportModal(
            button.dataset.reportType,
            button.dataset.targetId
        );
    });

    // CLOSE BUTTONS
    modal.querySelector(".report-modal-close")
        ?.addEventListener("click", closeReportModal);

    modal.querySelector(".report-cancel-button")
        ?.addEventListener("click", closeReportModal);

    // CLOSE WHEN CLICKING OUTSIDE
    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeReportModal();
        }
    });

    // CLOSE WITH ESCAPE
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
            closeReportModal();
        }
    });

    // SUBMIT REPORT WITHOUT RELOADING
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";

        message.hidden = true;

        try {
            const response = await fetch("report_submit.php", {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || "Could not submit report."
                );
            }

            message.textContent = data.message;
            message.classList.add("success");
            message.hidden = false;

            form.querySelectorAll("input, select, textarea")
                .forEach(input => {
                    if (input.type !== "hidden") {
                        input.disabled = true;
                    }
                });

            submitButton.textContent = "Report submitted";

        } catch (error) {
            console.error("Report submission error:", error);

            message.textContent = error.message;
            message.classList.add("error");
            message.hidden = false;

            submitButton.disabled = false;
            submitButton.textContent = "Submit report";
        }
    });
})();

function getCommentCsrfToken(comment) {
    return comment.querySelector('.comment-like-form input[name="csrf_token"]')?.value || "";
}

function createReplyElement(reply, postId, csrfToken) {
    const currentUserId = Number(
        document.querySelector(".feed-page")?.dataset.userId
    );

    const wrapper = document.createElement("div");
    wrapper.className = "comment comment-reply";
    wrapper.dataset.commentId = reply.comment_id;
    wrapper.dataset.postId = postId;

    const author = document.createElement("strong");
    const authorLink = document.createElement("a");
    authorLink.href = `profile.php?id=${encodeURIComponent(reply.user_id)}`;
    authorLink.className = "comment-author-link";
    authorLink.textContent = reply.username;
    author.appendChild(authorLink);

    const content = document.createElement("div");
    content.className = "feed-comment-content";

    if (reply.comment) {
        const text = document.createElement("p");
        text.textContent = reply.comment;
        content.appendChild(text);
    }

    if (Number(reply.user_id) === currentUserId) {
        const deleteForm = document.createElement("form");
        deleteForm.action = "comment_delete.php";
        deleteForm.method = "POST";
        deleteForm.className = "delete-comment-form";

        const fields = {
            csrf_token: csrfToken,
            comment_id: reply.comment_id,
            post_id: postId,
            return_to: "feed"
        };

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            deleteForm.appendChild(input);
        });

        const deleteButton = document.createElement("button");
        deleteButton.type = "submit";
        deleteButton.className = "delete-comment-button";
        deleteButton.textContent = "Delete";

        deleteForm.appendChild(deleteButton);
        content.appendChild(deleteForm);
    } else {
        const reportButton = document.createElement("button");
        reportButton.type = "button";
        reportButton.className = "report-button";
        reportButton.dataset.reportType = "comment";
        reportButton.dataset.targetId = reply.comment_id;
        reportButton.textContent = "Report";
        content.appendChild(reportButton);
    }

    const date = document.createElement("small");
    date.textContent = reply.created_at;

    const actions = document.createElement("div");
    actions.className = "comment-actions";

    const likeForm = document.createElement("form");
    likeForm.action = "comment_like.php";
    likeForm.method = "POST";
    likeForm.className = "comment-like-form";

    const likeFields = {
        csrf_token: csrfToken,
        comment_id: reply.comment_id,
        post_id: postId,
        return_to: "feed"
    };

    Object.entries(likeFields).forEach(([name, value]) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        likeForm.appendChild(input);
    });

    const likeButton = document.createElement("button");
    likeButton.type = "submit";
    likeButton.className = "comment-like-button";
    likeButton.setAttribute("aria-label", "Like comment");

    const heart = document.createElement("span");
    heart.className = "comment-like-heart";
    heart.textContent = "♡";

    const likeCount = document.createElement("span");
    likeCount.className = "comment-like-count";
    likeCount.textContent = Number(reply.like_count || 0);

    likeButton.append(heart, likeCount);
    likeForm.appendChild(likeButton);
    actions.appendChild(likeForm);

    wrapper.append(author, content, date, actions);

    return wrapper;
}

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-like-form");

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector(".comment-like-button");
    const heart = form.querySelector(".comment-like-heart");
    const count = form.querySelector(".comment-like-count");

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Comment like could not be updated.");
        }

        button.classList.toggle("active", data.liked);
        heart.textContent = data.liked ? "♥" : "♡";
        count.textContent = data.like_count;
        button.setAttribute(
            "aria-label",
            data.liked ? "Unlike comment" : "Like comment"
        );

    } catch (error) {
        console.error("Comment like error:", error);
    } finally {
        button.disabled = false;
    }
});

document.addEventListener("click", (event) => {
    const button = event.target.closest(".reply-toggle-button");

    if (!button) return;

    const comment = button.closest(".comment");
    const form = comment.querySelector(".comment-reply-form");

    if (!form) return;

    form.hidden = !form.hidden;

    if (!form.hidden) {
        form.querySelector('input[name="comment"]')?.focus();
    }
});

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-reply-form");

    if (!form) return;

    event.preventDefault();

    const comment = form.closest(".comment");
    const post = form.closest(".post-card");
    const input = form.querySelector('input[name="comment"]');
    const button = form.querySelector('button[type="submit"]');
    const repliesContainer = comment.querySelector(".comment-replies");
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;

    if (!input.value.trim()) return;

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Reply could not be added.");
        }

        const reply = {
            comment_id: data.comment_id,
            user_id: data.user_id,
            username: data.username,
            comment: data.comment,
            created_at: data.created_at,
            like_count: 0,
            user_liked: false
        };

        const replyElement = createReplyElement(
            reply,
            form.querySelector('input[name="post_id"]').value,
            csrfToken
        );

        repliesContainer.hidden = false;
        repliesContainer.insertBefore(
            replyElement,
            repliesContainer.querySelector(".load-more-replies-button")
        );

        repliesContainer.dataset.loaded = "1";
        repliesContainer.dataset.offset =
            Number(repliesContainer.dataset.offset || 0) + 1;

        const viewButton = comment.querySelector(".view-replies-button");

        if (viewButton) {
            const newCount = Number(viewButton.dataset.replyCount || 0) + 1;
            viewButton.dataset.replyCount = newCount;
            viewButton.textContent = "Hide replies";
        } else {
            const newButton = document.createElement("button");
            newButton.type = "button";
            newButton.className = "view-replies-button";
            newButton.dataset.replyCount = "1";
            newButton.textContent = "Hide replies";
            comment.insertBefore(newButton, repliesContainer);
        }

        let loadMoreButton = repliesContainer.querySelector(".load-more-replies-button");

        if (!loadMoreButton) {
            loadMoreButton = document.createElement("button");
            loadMoreButton.type = "button";
            loadMoreButton.className = "load-more-replies-button";
            loadMoreButton.textContent = "View more replies";
            repliesContainer.appendChild(loadMoreButton);
        }

        const replyCount = Number(
            comment.querySelector(".view-replies-button").dataset.replyCount
        );

        loadMoreButton.hidden =
            Number(repliesContainer.dataset.offset) >= replyCount;

        post.querySelector(".comment-count").textContent = data.comment_count;

        form.reset();
        form.hidden = true;

    } catch (error) {
        console.error("Reply error:", error);
        alert(error.message);
    } finally {
        button.disabled = false;
    }
});

async function loadCommentReplies(comment, button) {
    const repliesContainer = comment.querySelector(".comment-replies");
    const postId = comment.dataset.postId;
    const parentCommentId = comment.dataset.commentId;
    const csrfToken = getCommentCsrfToken(comment);
    const offset = Number(repliesContainer.dataset.offset || 0);
    const isLoadMoreButton = button.classList.contains("load-more-replies-button");

    button.disabled = true;
    button.textContent = "Loading...";

    try {
        const formData = new FormData();
        formData.append("csrf_token", csrfToken);
        formData.append("parent_comment_id", parentCommentId);
        formData.append("offset", offset);

        const response = await fetch("/get_comment_replies.php", {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Replies could not be loaded.");
        }

        data.replies.forEach((reply) => {
            const replyElement = createReplyElement(reply, postId, csrfToken);
            repliesContainer.insertBefore(
                replyElement,
                repliesContainer.querySelector(".load-more-replies-button")
            );
        });

        repliesContainer.dataset.loaded = "1";
        repliesContainer.dataset.offset = data.next_offset;

        let loadMoreButton = repliesContainer.querySelector(".load-more-replies-button");

        if (data.has_more) {
            if (!loadMoreButton) {
                loadMoreButton = document.createElement("button");
                loadMoreButton.type = "button";
                loadMoreButton.className = "load-more-replies-button";
                repliesContainer.appendChild(loadMoreButton);
            }

            loadMoreButton.textContent = "View more replies";
            loadMoreButton.disabled = false;
            loadMoreButton.hidden = false;
        } else if (loadMoreButton) {
            loadMoreButton.remove();
        }

        repliesContainer.hidden = false;

        const viewButton = comment.querySelector(".view-replies-button");

        if (viewButton) {
            viewButton.textContent = "Hide replies";
        }

    } catch (error) {
        console.error("Load replies error:", error);

        button.textContent = isLoadMoreButton
            ? "Try again"
            : "Try again";

        button.disabled = false;
        alert(error.message);
        return;
    }

    if (!isLoadMoreButton) {
        if (button.classList.contains("view-replies-button")) {
            button.textContent = "Hide replies";
        }
    }
    button.disabled = false;
}


document.addEventListener("click", async (event) => {
    const viewButton = event.target.closest(".view-replies-button");

    if (viewButton) {
        const comment = viewButton.closest(".comment");
        const repliesContainer = comment.querySelector(".comment-replies");

        if (!repliesContainer.hidden) {
            repliesContainer.hidden = true;
            viewButton.textContent = `View ${viewButton.dataset.replyCount} ${Number(viewButton.dataset.replyCount) === 1 ? "reply" : "replies"}`;
            return;
        }

        if (repliesContainer.dataset.loaded === "1") {
            repliesContainer.querySelectorAll(".comment-reply").forEach(reply => reply.remove());

            const loadMoreButton = repliesContainer.querySelector(".load-more-replies-button");
            if (loadMoreButton) loadMoreButton.remove();

            repliesContainer.dataset.offset = "0";
            repliesContainer.dataset.loaded = "0";
        }

        await loadCommentReplies(comment, viewButton);
        return;
    }

    const moreButton = event.target.closest(".load-more-replies-button");

    if (moreButton) {
        const comment = moreButton.closest(".comment");
        await loadCommentReplies(comment, moreButton);
    }
});

