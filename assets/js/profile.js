
// Profile Picture Modal
const profileAvatarImage = document.getElementById("profile-avatar-image");
const profileImageModal = document.getElementById("profile-image-modal");
const profileModalImage = document.getElementById("profile-modal-image");
const profileImageClose = document.querySelector(".profile-image-modal-close");

if (
    profileAvatarImage &&
    profileImageModal &&
    profileModalImage &&
    profileImageClose
) {
    profileAvatarImage.addEventListener("click", () => {
        profileModalImage.src = profileAvatarImage.src;
        profileModalImage.alt = profileAvatarImage.alt;

        profileImageModal.classList.add("active");
        profileImageModal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    });

    const closeProfileImageModal = () => {
        profileImageModal.classList.remove("active");
        profileImageModal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    };

    profileImageClose.addEventListener("click", closeProfileImageModal);

    profileImageModal.addEventListener("click", (event) => {
        if (event.target === profileImageModal) {
            closeProfileImageModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeProfileImageModal();
        }
    });
}


// Profile pinning without page reload
document.querySelectorAll(".profile-pin-form").forEach(form => {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const button = form.querySelector(".profile-pin-button");

        if (!button || button.disabled) return;

        const isCurrentlyPinned = button.classList.contains("is-pinned");

        const confirmation = isCurrentlyPinned
            ? "Unpin this post from your profile?"
            : "Pin this post to your profile?";

        if (!confirm(confirmation)) return;

        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (result.status === "pin_limit") {
                    showPinMessage(
                        "You've reached the maximum of 3 pinned posts! Unpin one before pinning another."
                    );
                } else {
                    showPinMessage(
                        "We couldn't update your pinned post. Please try again."
                    );
                }

                return;
            }

            const isPinned = result.status === "pinned";

            button.classList.toggle("is-pinned", isPinned);

            // Reorder posts so pinned posts appear first
            const postGrid = document.getElementById("profile-post-grid");

            if (postGrid) {
                const postItems = Array.from(
                    postGrid.querySelectorAll(".profile-post-item")
                );

                postItems.sort((a, b) => {
                    const aPinned = a.querySelector(".profile-pin-button")
                        ?.classList.contains("is-pinned") ? 1 : 0;

                    const bPinned = b.querySelector(".profile-pin-button")
                        ?.classList.contains("is-pinned") ? 1 : 0;

                    return bPinned - aPinned;
                });

                postItems.forEach(item => postGrid.appendChild(item));
            }

            button.setAttribute(
                "aria-label",
                isPinned ? "Unpin post" : "Pin post to profile"
            );

            button.setAttribute(
                "title",
                isPinned ? "Unpin post" : "Pin to profile"
            );

            showPinMessage(
                isPinned
                    ? "Your post is now pinned to your profile!"
                    : "Your post has been unpinned."
            );

        } catch (error) {
            console.error("Profile pin error:", error);

            showPinMessage(
                "Something went wrong. Please try again."
            );
        } finally {
            button.disabled = false;
        }
    });
});

function showPinMessage(message) {
    let messageElement = document.querySelector(".profile-pin-message");

    if (!messageElement) {
        messageElement = document.createElement("p");
        messageElement.className = "profile-pin-message";
        messageElement.setAttribute("role", "status");

        const postsSection = document.querySelector(".profile-posts");

        if (postsSection) {
            postsSection.parentNode.insertBefore(
                messageElement,
                postsSection
            );
        }
    }

    messageElement.textContent = message;
}
// REPORT PROFILE
const reportProfileButton = document.querySelector(
    '.report-button[data-report-type="user"]'
);

const reportModal = document.getElementById("report-modal");
const reportForm = document.getElementById("report-form");
const reportTargetId = document.getElementById("report-target-id");
const reportType = document.getElementById("report-type");
const reportMessage = document.getElementById("report-message");

if (
    reportProfileButton &&
    reportModal &&
    reportForm &&
    reportTargetId &&
    reportType &&
    reportMessage
) {
    const closeReportModal = () => {
        reportModal.classList.remove("active");
        reportModal.hidden = true;
        document.body.style.overflow = "";
    };

    // Open report modal
    reportProfileButton.addEventListener("click", () => {
        reportTargetId.value = reportProfileButton.dataset.targetId;
        reportType.value = "user";

        reportForm.reset();
        reportTargetId.value = reportProfileButton.dataset.targetId;
        reportType.value = "user";

        reportMessage.textContent = "";
        reportMessage.hidden = true;

        reportModal.hidden = false;
        reportModal.classList.add("active");
        document.body.style.overflow = "hidden";
    });

    const closeButton = reportModal.querySelector(".report-modal-close");

    if (closeButton) {
        closeButton.addEventListener("click", closeReportModal);
    }

    const cancelButton = reportModal.querySelector(".report-cancel-button");

    if (cancelButton) {
        cancelButton.addEventListener("click", closeReportModal);
    }

    reportModal.addEventListener("click", (event) => {
        if (event.target === reportModal) {
            closeReportModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !reportModal.hidden) {
            closeReportModal();
        }
    });

    reportForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const submitButton = reportForm.querySelector(
            ".report-submit-button"
        );

        if (!submitButton || submitButton.disabled) return;

        submitButton.disabled = true;
        reportMessage.hidden = true;

        try {
            const response = await fetch("report_submit.php", {
                method: "POST",
                body: new FormData(reportForm),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                reportMessage.textContent =
                    result.message || "We couldn't submit your report. Please try again.";

                reportMessage.hidden = false;
                return;
            }

            reportMessage.textContent =
                result.message || "Your report has been submitted successfully.";

            reportMessage.hidden = false;

            reportForm.reset();

        } catch (error) {
            console.error("Profile report error:", error);

            reportMessage.textContent =
                "Something went wrong. Please try again.";

            reportMessage.hidden = false;

        } finally {
            submitButton.disabled = false;
        }
    });
}
