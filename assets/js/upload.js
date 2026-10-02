const mediaInput = document.getElementById("media");
const mediaPreview = document.getElementById("mediaPreview");

const MAX_FILES = 5;

let selectedFiles = [];

mediaInput.addEventListener("change", () => {

    const newFiles = Array.from(mediaInput.files);

    for (const file of newFiles) {

        // Stop duplicates
        const alreadyExists = selectedFiles.some(existingFile =>
            existingFile.name === file.name &&
            existingFile.size === file.size &&
            existingFile.lastModified === file.lastModified
        );

        if (alreadyExists) {
            continue;
        }

        if (selectedFiles.length >= MAX_FILES) {
            alert("You can upload a maximum of 5 files.");
            break;
        }

        selectedFiles.push(file);
    }

    updateInputFiles();
    renderPreviews();
});


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

        // IMAGE
        if (file.type.startsWith("image/")) {

            const image = document.createElement("img");

            image.src = objectUrl;
            image.alt = file.name;

            image.onload = () => {
                URL.revokeObjectURL(objectUrl);
            };

            item.appendChild(image);
        }

        // VIDEO
        else if (file.type.startsWith("video/")) {

            const video = document.createElement("video");

            video.src = objectUrl;
            video.controls = true;
            video.muted = true;

            video.onloadedmetadata = () => {
                URL.revokeObjectURL(objectUrl);
            };

            item.appendChild(video);
        }

        // REMOVE BUTTON
        const removeButton = document.createElement("button");

        removeButton.type = "button";
        removeButton.classList.add("media-remove");
        removeButton.textContent = "×";
        removeButton.setAttribute("aria-label", `Remove ${file.name}`);

        removeButton.addEventListener("click", () => {
            removeFile(index);
        });

        item.appendChild(removeButton);

        // FILE NAME
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