(() => {
    const imageModal = document.getElementById("image-modal");
    const modalImage = document.getElementById("modal-image");
    const closeButton = document.querySelector(".image-modal-close");

    if (!imageModal || !modalImage || !closeButton) return;

    function closeModal() {
        imageModal.classList.remove("active");
        imageModal.setAttribute("aria-hidden", "true");
    }

    document.addEventListener("click", (event) => {
        const image = event.target.closest(
            ".post-comment-media, .comment-preview-image"
        );

        if (!image || !image.src) return;

        event.preventDefault();
        modalImage.src = image.currentSrc || image.src;
        modalImage.alt = image.alt;
        imageModal.classList.add("active");
        imageModal.setAttribute("aria-hidden", "false");
    });

    closeButton.addEventListener("click", closeModal);

    imageModal.addEventListener("click", (event) => {
        if (event.target === imageModal) closeModal();
    });
})();

(() => {
    const form = document.querySelector(".post-detail-comment-form");
    if (!form) return;

    const input = form.querySelector(".comment-media-input");
    const preview = form.querySelector(".comment-media-preview");
    const image = preview?.querySelector(".comment-preview-image");
    const removeButton = preview?.querySelector(".comment-preview-remove");

    if (!input || !preview || !image || !removeButton) return;

    let previewUrl = null;

    function clearPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        input.value = "";
        image.removeAttribute("src");
        preview.hidden = true;
    }

    input.addEventListener("change", () => {
        const file = input.files[0];

        if (!file) {
            clearPreview();
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ];

        if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            alert("Choose a JPG, PNG, WEBP or GIF smaller than 5 MB.");
            clearPreview();
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);

        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        preview.hidden = false;
    });

    removeButton.addEventListener("click", clearPreview);
})();

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(
        ".post-detail-comment-list .delete-comment-form"
    );

    if (!form) return;

    event.preventDefault();

    if (!confirm("Are you sure you want to delete this comment?")) return;

    const button = form.querySelector(".delete-comment-button");
    const comment = form.closest(".post-detail-comment");
    const commentsList = form.closest(".post-detail-comment-list");
    const commentCount = document.querySelector(
        ".post-detail-actions .comment-icon + span"
    );

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

        if (!response.ok) throw new Error("Comment delete request failed.");

        const data = await response.json();

        if (!data.success) {
            throw new Error("Comment could not be deleted.");
        }

        comment.remove();

        if (commentCount) {
            commentCount.textContent = data.comment_count;
        }

        const remainingComments =
            commentsList.querySelectorAll(":scope > .post-detail-comment").length;

        const loadMoreButton = document.querySelector(
            ".post-detail-comments .load-more-comments-button"
        );

        if (loadMoreButton) {
            loadMoreButton.dataset.offset = remainingComments;

            if (Number(data.comment_count) <= remainingComments) {
                loadMoreButton.remove();
            }
        }

        const emptyMessage = commentsList.querySelector(
            ".post-detail-no-comments"
        );

        if (Number(data.comment_count) === 0 && !emptyMessage) {
            const message = document.createElement("p");
            message.className = "post-detail-no-comments";
            message.textContent = "No comments yet. Be the first to comment.";
            commentsList.appendChild(message);
        }
    } catch (error) {
        console.error("Delete comment error:", error);
        button.disabled = false;
    }
});

document.querySelector(".post-detail-comment-form")
    ?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        const commentsList = document.querySelector(
            ".post-detail-comment-list"
        );

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

            if (!response.ok) throw new Error("Comment submission failed.");

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Could not post comment.");
            }

            commentsList.querySelector(".post-detail-no-comments")?.remove();

            const article = document.createElement("article");
            article.className = "post-detail-comment";
            article.dataset.commentId = data.comment_id;

            const avatarLink = document.createElement("a");
            avatarLink.href = `profile.php?id=${data.user_id}`;
            avatarLink.className = "comment-avatar-link";

            const avatar = document.createElement("img");
            avatar.className = "comment-avatar";
            avatar.src = data.profile_image || "images/default-profile.jpg";
            avatar.alt = `${data.username}'s profile picture`;

            avatarLink.appendChild(avatar);

            const content = document.createElement("div");
            content.className = "comment-content";

            const meta = document.createElement("div");
            meta.className = "comment-meta";

            const author = document.createElement("strong");
            const authorLink = document.createElement("a");

            authorLink.href = `profile.php?id=${data.user_id}`;
            authorLink.className = "comment-author-link";
            authorLink.textContent = data.username;

            author.appendChild(authorLink);

            const date = document.createElement("span");
            const parsedDate = new Date(data.created_at.replace(" ", "T"));

            date.textContent = parsedDate.toLocaleDateString("en-GB", {
                day: "numeric",
                month: "short"
            });

            meta.append(author, date);

            const deleteForm = document.createElement("form");
            deleteForm.action = "comment_delete.php";
            deleteForm.method = "POST";
            deleteForm.className = "delete-comment-form";

            const fields = {
                comment_id: data.comment_id,
                post_id: form.querySelector('[name="post_id"]').value,
                csrf_token: form.querySelector('[name="csrf_token"]').value,
                return_to: "post"
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
            meta.appendChild(deleteForm);
            content.appendChild(meta);

            if (data.comment) {
                const text = document.createElement("p");
                text.textContent = data.comment;
                text.style.whiteSpace = "pre-line";
                content.appendChild(text);
            }

            if (data.media_url) {
                const imageLink = document.createElement("a");
                imageLink.href = data.media_url;

                const image = document.createElement("img");
                image.className = "post-comment-media";
                image.src = data.media_url;
                image.alt = "Comment attachment";
                image.loading = "lazy";

                imageLink.appendChild(image);
                content.appendChild(imageLink);
            }

            article.append(avatarLink, content);
            commentsList.appendChild(article);

            const commentCount = document.querySelector(
                ".post-detail-actions .comment-icon + span"
            );

            if (commentCount) {
                commentCount.textContent = data.comment_count;
            }

            form.querySelector('[name="comment"]').value = "";
            form.querySelector(".comment-preview-remove")?.click();

        } catch (error) {
            console.error("Comment submission error:", error);
            alert(error.message || "Something went wrong.");
        } finally {
            button.disabled = false;
        }
    });

document.querySelectorAll(
    ".post-detail-comments .load-more-comments-button"
).forEach(button => {
    button.addEventListener("click", async () => {
        const commentsList = document.querySelector(
            ".post-detail-comment-list"
        );

        const postId = button.dataset.postId;
        const offset = Number(button.dataset.offset);

        button.disabled = true;
        button.textContent = "Loading...";

        try {
            const response = await fetch(
                `load_more_comments.php?post_id=${encodeURIComponent(postId)}&offset=${offset}`
            );

            if (!response.ok) throw new Error("Failed to load comments.");

            const data = await response.json();

            if (!Array.isArray(data.comments)) {
                throw new Error("Invalid comments response.");
            }

            const currentUserId = Number(
                document.querySelector(".post-detail-page")?.dataset.userId || 0
            );

            data.comments.forEach(comment => {
                const article = document.createElement("article");
                article.className = "post-detail-comment";
                article.dataset.commentId = comment.comment_id;

                const avatarLink = document.createElement("a");
                avatarLink.href = `profile.php?id=${comment.user_id}`;
                avatarLink.className = "comment-avatar-link";

                const avatar = document.createElement("img");
                avatar.className = "comment-avatar";
                avatar.src = comment.profile_image || "images/default-profile.jpg";
                avatar.alt = `${comment.username}'s profile picture`;

                avatarLink.appendChild(avatar);

                const content = document.createElement("div");
                content.className = "comment-content";

                const meta = document.createElement("div");
                meta.className = "comment-meta";

                const author = document.createElement("strong");
                const authorLink = document.createElement("a");

                authorLink.href = `profile.php?id=${comment.user_id}`;
                authorLink.className = "comment-author-link";
                authorLink.textContent = comment.username;

                author.appendChild(authorLink);

                const date = document.createElement("span");
                const parsedDate = new Date(
                    comment.created_at.replace(" ", "T")
                );

                date.textContent = parsedDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short"
                });

                meta.append(author, date);

                if (Number(comment.user_id) !== currentUserId) {
                    const reportButton = document.createElement("button");

                    reportButton.type = "button";
                    reportButton.className = "report-button";
                    reportButton.textContent = "Report";
                    reportButton.dataset.reportType = "comment";
                    reportButton.dataset.targetId = comment.comment_id;

                    meta.appendChild(reportButton);
                } else {
                    const deleteForm = document.createElement("form");
                    deleteForm.action = "comment_delete.php";
                    deleteForm.method = "POST";
                    deleteForm.className = "delete-comment-form";

                    const csrfToken = document.querySelector(
                        '.post-detail-comment-form input[name="csrf_token"]'
                    )?.value || "";

                    const fields = {
                        comment_id: comment.comment_id,
                        post_id: postId,
                        csrf_token: csrfToken,
                        return_to: "post"
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
                    meta.appendChild(deleteForm);
                }

                content.appendChild(meta);

                if (comment.comment) {
                    const text = document.createElement("p");
                    text.textContent = comment.comment;
                    text.style.whiteSpace = "pre-line";
                    content.appendChild(text);
                }

                if (comment.media_file) {
                    const imageUrl =
                        `uploads/comments/${encodeURIComponent(comment.media_file)}`;

                    const imageLink = document.createElement("a");
                    imageLink.href = imageUrl;
                    imageLink.className = "comment-media-link";

                    const image = document.createElement("img");
                    image.className = "post-comment-media";
                    image.src = imageUrl;
                    image.alt = "Comment attachment";
                    image.loading = "lazy";

                    imageLink.appendChild(image);
                    content.appendChild(imageLink);
                }

                article.append(avatarLink, content);
                commentsList.appendChild(article);
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

document.querySelector(
    '.post-detail-actions form[action="save_post.php"]'
)?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    const button = form.querySelector("button");
    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error("Save request failed.");

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Save failed.");
        }

        button.classList.toggle("active", data.saved);
        button.setAttribute(
            "aria-label",
            data.saved ? "Remove from saved" : "Save post"
        );
        button.title = data.saved ? "Saved" : "Save";
    } catch (error) {
        console.error("Save error:", error);
    } finally {
        button.disabled = false;
    }
});

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

    document.addEventListener("click", (event) => {
        const button = event.target.closest(".report-button");

        if (!button) return;

        openReportModal(
            button.dataset.reportType,
            button.dataset.targetId
        );
    });

    modal.querySelector(".report-modal-close")
        ?.addEventListener("click", closeReportModal);

    modal.querySelector(".report-cancel-button")
        ?.addEventListener("click", closeReportModal);

    modal.addEventListener("click", (event) => {
        if (event.target === modal) closeReportModal();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
            closeReportModal();
        }
    });

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
                    data.message || data.error || "Could not submit report."
                );
            }

            message.textContent = data.message;
            message.classList.add("success");
            message.hidden = false;

            form.querySelectorAll("input, select, textarea")
                .forEach(input => {
                    if (input.type !== "hidden") input.disabled = true;
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

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-like-form");

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector(".comment-like-button");
    const count = form.querySelector(".comment-like-count");

    if (!button || !count || button.disabled) return;

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
            throw new Error(
                data.message || "Could not update comment like."
            );
        }

        button.classList.toggle("active", data.liked);
        button.setAttribute("aria-pressed", String(data.liked));
        button.textContent = data.liked ? "♥" : "♡";
        count.textContent = data.like_count;

    } catch (error) {
        console.error("Comment like error:", error);
        alert(error.message || "Something went wrong. Please try again.");
    } finally {
        button.disabled = false;
    }
});

document.addEventListener("click", (event) => {
    const button = event.target.closest(".reply-toggle-button");

    if (!button) return;

    const comment = button.closest(".post-detail-comment");
    const form = comment?.querySelector(
        ":scope > .comment-content > .comment-reply-form"
    );

    if (!form) return;

    form.hidden = !form.hidden;

    if (!form.hidden) {
        form.querySelector("textarea")?.focus();
    }
});

function createPostReplyElement(reply, postId, csrfToken) {
    const currentUserId = Number(
        document.querySelector(".post-detail-page")?.dataset.userId || 0
    );

    const article = document.createElement("article");
    article.className = "post-detail-comment post-detail-reply";
    article.dataset.commentId = reply.comment_id;

    const avatarLink = document.createElement("a");
    avatarLink.href = `profile.php?id=${reply.user_id}`;
    avatarLink.className = "comment-avatar-link";

    const avatar = document.createElement("img");
    avatar.className = "comment-avatar";
    avatar.src = reply.profile_image || "images/default-profile.jpg";
    avatar.alt = `${reply.username}'s profile picture`;

    avatarLink.appendChild(avatar);

    const content = document.createElement("div");
    content.className = "comment-content";

    const meta = document.createElement("div");
    meta.className = "comment-meta";

    const author = document.createElement("strong");
    const authorLink = document.createElement("a");

    authorLink.href = `profile.php?id=${reply.user_id}`;
    authorLink.className = "comment-author-link";
    authorLink.textContent = reply.username;

    author.appendChild(authorLink);

    const date = document.createElement("span");
    const parsedDate = new Date(reply.created_at.replace(" ", "T"));

    date.textContent = parsedDate.toLocaleDateString("en-GB", {
        day: "numeric",
        month: "short"
    });

    meta.append(author, date);

    if (Number(reply.user_id) === currentUserId) {
        const deleteForm = document.createElement("form");
        deleteForm.action = "comment_delete.php";
        deleteForm.method = "POST";
        deleteForm.className = "delete-comment-form";

        const fields = {
            comment_id: reply.comment_id,
            post_id: postId,
            csrf_token: csrfToken,
            return_to: "post"
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
        meta.appendChild(deleteForm);
    } else {
        const reportButton = document.createElement("button");
        reportButton.type = "button";
        reportButton.className = "report-button";
        reportButton.dataset.reportType = "comment";
        reportButton.dataset.targetId = reply.comment_id;
        reportButton.textContent = "Report";

        meta.appendChild(reportButton);
    }

    content.appendChild(meta);

    if (reply.comment) {
        const text = document.createElement("p");
        text.textContent = reply.comment;
        text.style.whiteSpace = "pre-line";
        content.appendChild(text);
    }

    const actions = document.createElement("div");
    actions.className = "comment-actions";

    const likeForm = document.createElement("form");
    likeForm.action = "comment_like.php";
    likeForm.method = "POST";
    likeForm.className = "comment-like-form";

    const likeFields = {
        comment_id: reply.comment_id,
        post_id: postId,
        return_to: "post",
        csrf_token: csrfToken
    };

    Object.entries(likeFields).forEach(([name, value]) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        likeForm.appendChild(input);
    });

    const liked = Boolean(Number(reply.user_liked));

    const likeButton = document.createElement("button");
    likeButton.type = "submit";
    likeButton.className = `comment-like-button ${liked ? "active" : ""}`;
    likeButton.setAttribute("aria-label", liked ? "Unlike reply" : "Like reply");
    likeButton.setAttribute("aria-pressed", String(liked));
    likeButton.textContent = liked ? "♥" : "♡";

    const likeCount = document.createElement("span");
    likeCount.className = "comment-like-count";
    likeCount.textContent = Number(reply.like_count || 0);

    likeForm.append(likeButton, likeCount);
    actions.appendChild(likeForm);
    content.appendChild(actions);

    article.append(avatarLink, content);

    return article;
}

async function loadPostCommentReplies(comment, button) {
    const repliesContainer = comment.querySelector(
        ":scope > .comment-content > .comment-replies"
    );

    if (!repliesContainer) return;

    const postId = repliesContainer.dataset.postId;
    const parentCommentId = comment.dataset.commentId;

    const csrfToken =
        comment.querySelector('.comment-like-form input[name="csrf_token"]')?.value ||
        document.querySelector(".post-detail-page")?.dataset.repliesCsrf ||
        "";

    const offset = Number(repliesContainer.dataset.offset || 0);
    const isLoadMore = button.classList.contains("load-more-replies-button");

    button.disabled = true;
    button.textContent = "Loading...";

    try {
        const formData = new FormData();

        formData.append("csrf_token", csrfToken);
        formData.append("parent_comment_id", parentCommentId);
        formData.append("offset", offset);

        const response = await fetch("/witchy/get_comment_replies.php", {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success || !Array.isArray(data.replies)) {
            throw new Error(data.message || "Replies could not be loaded.");
        }

        let loadMoreButton = repliesContainer.querySelector(
            ".load-more-replies-button"
        );

        data.replies.forEach(reply => {
            const replyElement = createPostReplyElement(
                reply,
                postId,
                csrfToken
            );

            repliesContainer.insertBefore(replyElement, loadMoreButton);
        });

        repliesContainer.dataset.loaded = "1";
        repliesContainer.dataset.offset = data.next_offset;

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
        } else {
            loadMoreButton?.remove();
        }

        repliesContainer.hidden = false;

        const viewButton = comment.querySelector(
            ".post-detail-view-replies"
        );

        if (viewButton) {
            viewButton.textContent = "Hide replies";
            viewButton.setAttribute("aria-expanded", "true");
        }

    } catch (error) {
        console.error("Load post replies error:", error);

        button.textContent = "Try again";
        button.disabled = false;

        alert(error.message || "Replies could not be loaded.");
        return;
    }

    button.disabled = false;

    if (isLoadMore) {
        button.textContent = "View more replies";
    }
}

document.addEventListener("click", async (event) => {
    const viewButton = event.target.closest(".post-detail-view-replies");

    if (viewButton) {
        const currentUserId = Number(
            document.querySelector(".post-detail-page")?.dataset.userId || 0
        );

        if (currentUserId === 0) return;

        const comment = viewButton.closest(".post-detail-comment");

        const repliesContainer = comment?.querySelector(
            ":scope > .comment-content > .comment-replies"
        );

        if (!repliesContainer) return;

        if (!repliesContainer.hidden) {
            repliesContainer.hidden = true;

            viewButton.textContent =
                `View ${viewButton.dataset.replyCount} ${
                    Number(viewButton.dataset.replyCount) === 1
                        ? "reply"
                        : "replies"
                }`;

            viewButton.setAttribute("aria-expanded", "false");
            return;
        }

        if (repliesContainer.dataset.loaded === "1") {
            repliesContainer.querySelectorAll(".post-detail-reply")
                .forEach(reply => reply.remove());

            repliesContainer.querySelector(".load-more-replies-button")
                ?.remove();

            repliesContainer.dataset.offset = "0";
            repliesContainer.dataset.loaded = "0";
        }

        await loadPostCommentReplies(comment, viewButton);
        return;
    }

    const moreButton = event.target.closest(
        ".load-more-replies-button"
    );

    if (moreButton) {
        const currentUserId = Number(
            document.querySelector(".post-detail-page")?.dataset.userId || 0
        );

        if (currentUserId === 0) return;

        const comment = moreButton.closest(".post-detail-comment");

        if (comment) {
            await loadPostCommentReplies(comment, moreButton);
        }
    }
});

document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-reply-form");

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const textarea = form.querySelector("textarea");
    const comment = form.closest(".post-detail-comment");
    const repliesContainer = comment?.querySelector(
        ":scope > .comment-content > .comment-replies"
    );

    if (!button || !textarea || !repliesContainer) return;

    if (!textarea.value.trim()) {
        textarea.focus();
        return;
    }

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
            throw new Error(data.message || "Could not post reply.");
        }

        const postId = form.querySelector('[name="post_id"]').value;
        const csrfToken = form.querySelector('[name="csrf_token"]').value;

        const reply = createPostReplyElement({
            comment_id: data.comment_id,
            user_id: data.user_id,
            username: data.username,
            profile_image: data.profile_image,
            created_at: data.created_at,
            comment: data.comment,
            like_count: 0,
            user_liked: 0
        }, postId, csrfToken);

        const loadMoreButton = repliesContainer.querySelector(
            ".load-more-replies-button"
        );

        repliesContainer.insertBefore(reply, loadMoreButton);
        repliesContainer.hidden = false;
        repliesContainer.dataset.loaded = "1";
        repliesContainer.dataset.offset =
            Number(repliesContainer.dataset.offset || 0) + 1;

        const viewButton = comment.querySelector(
            ".post-detail-view-replies"
        );

        let replyCount = Number(viewButton?.dataset.replyCount || 0) + 1;

        if (!viewButton) {
            const newButton = document.createElement("button");
            newButton.type = "button";
            newButton.className =
                "view-replies-button post-detail-view-replies";
            newButton.dataset.commentId = comment.dataset.commentId;
            newButton.dataset.replyCount = "1";
            newButton.textContent = "Hide replies";
            newButton.setAttribute("aria-expanded", "true");

            repliesContainer.before(newButton);
        } else {
            viewButton.dataset.replyCount = replyCount;
            viewButton.textContent = "Hide replies";
            viewButton.setAttribute("aria-expanded", "true");
        }

        const commentCount = document.querySelector(
            ".post-detail-actions .comment-icon + span"
        );

        if (commentCount) {
            commentCount.textContent = data.comment_count;
        }

        textarea.value = "";
        form.hidden = true;

    } catch (error) {
        console.error("Reply submission error:", error);
        alert(error.message || "Something went wrong.");
    } finally {
        button.disabled = false;
    }
});