const mediaInput = document.getElementById("media");
const mediaPreview = document.getElementById("mediaPreview");

const MAX_FILES = 5;

let selectedFiles = [];

const existingMediaItems = Array.from(
    document.querySelectorAll(".current-media-item")
);

const removedMediaIds = new Set();

function getRemainingExistingCount() {
    return existingMediaItems.length - removedMediaIds.size;
}

function updateInputFiles() {
    const dataTransfer = new DataTransfer();

    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });

    mediaInput.files = dataTransfer.files;
}

function renderPreviews() {
    mediaPreview.innerHTML = "";

    selectedFiles.forEach((file, index) => {
        const item = document.createElement("div");
        item.classList.add("media-preview-item");

        const objectUrl = URL.createObjectURL(file);

        if (file.type.startsWith("image/")) {
            const image = document.createElement("img");

            image.src = objectUrl;
            image.alt = file.name;

            image.onload = () => {
                URL.revokeObjectURL(objectUrl);
            };

            item.appendChild(image);
        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");

            video.src = objectUrl;
            video.controls = true;
            video.muted = true;

            video.onloadedmetadata = () => {
                URL.revokeObjectURL(objectUrl);
            };

            item.appendChild(video);
        }

        const removeButton = document.createElement("button");

        removeButton.type = "button";
        removeButton.classList.add("media-remove");
        removeButton.textContent = "×";
        removeButton.setAttribute("aria-label", `Remove ${file.name}`);

        removeButton.addEventListener("click", () => {
            removeFile(index);
        });

        item.appendChild(removeButton);

        const fileName = document.createElement("small");

        fileName.classList.add("media-file-name");
        fileName.textContent = file.name;

        item.appendChild(fileName);

        mediaPreview.appendChild(item);
    });
}

function removeFile(index) {
    selectedFiles.splice(index, 1);

    updateInputFiles();
    renderPreviews();
}

function addRemovalInput(mediaId) {
    const hiddenInput = document.createElement("input");

    hiddenInput.type = "hidden";
    hiddenInput.name = "remove_media[]";
    hiddenInput.value = mediaId;
    hiddenInput.dataset.removalId = mediaId;

    document.querySelector(".edit-form").appendChild(hiddenInput);
}

existingMediaItems.forEach(item => {
    const removeButton = item.querySelector("[data-remove-existing]");

    if (!removeButton) {
        return;
    }

    removeButton.addEventListener("click", () => {
        const mediaId = removeButton.dataset.removeExisting;

        if (removedMediaIds.has(mediaId)) {
            return;
        }

        removedMediaIds.add(mediaId);
        item.classList.add("media-marked-for-removal");

        addRemovalInput(mediaId);
    });
});

mediaInput.addEventListener("change", () => {
    const newFiles = Array.from(mediaInput.files);

    for (const file of newFiles) {
        const alreadyExists = selectedFiles.some(existingFile =>
            existingFile.name === file.name &&
            existingFile.size === file.size &&
            existingFile.lastModified === file.lastModified
        );

        if (alreadyExists) {
            continue;
        }

        const totalCount = getRemainingExistingCount() + selectedFiles.length;

        if (totalCount >= MAX_FILES) {
            alert("A post can have a maximum of 5 files.");
            break;
        }

        selectedFiles.push(file);
    }

    updateInputFiles();
    renderPreviews();
});