(() => {
    const isFeed = window.location.pathname.endsWith("/feed.php");

    if (!isFeed) return;

    const feedScrollKey =
        `witchy-feed-scroll:${window.location.pathname}${window.location.search}`;

    history.scrollRestoration = "manual";

    const savedScroll = sessionStorage.getItem(feedScrollKey);

    if (savedScroll !== null) {
        document.documentElement.classList.add("feed-restoring");
    }

    function restoreScroll() {
        const position = sessionStorage.getItem(feedScrollKey);

        if (position === null) {
            document.documentElement.classList.remove("feed-restoring");
            return;
        }

        requestAnimationFrame(() => {
            window.scrollTo({
                top: Number(position),
                left: 0,
                behavior: "instant"
            });

            sessionStorage.removeItem(feedScrollKey);
            document.documentElement.classList.remove("feed-restoring");
        });
    }

    window.addEventListener("pageshow", restoreScroll);

    document.addEventListener("click", (event) => {
        const postLink = event.target.closest('a[href*="post.php"]');

        if (!postLink) return;

        const targetUrl = new URL(postLink.href, window.location.href);

        if (
            targetUrl.origin !== window.location.origin ||
            !targetUrl.pathname.endsWith("/post.php")
        ) {
            return;
        }

        sessionStorage.setItem(feedScrollKey, String(window.scrollY));
        document.documentElement.classList.add("feed-restoring");
    });
})();