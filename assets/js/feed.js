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