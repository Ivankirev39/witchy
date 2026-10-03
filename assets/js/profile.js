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