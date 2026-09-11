<div class="hacer-login-bg" aria-hidden="true">
    <video
        class="hacer-login-bg__video"
        autoplay
        muted
        loop
        playsinline
        preload="auto"
        disablepictureinpicture
        disableremoteplayback
    >
        <source src="{{ asset('videos/admin-login-bg.mp4') }}" type="video/mp4">
    </video>
    <div class="hacer-login-bg__veil"></div>
</div>

<script>
    (() => {
        const video = document.querySelector('.hacer-login-bg__video');

        if (! (video instanceof HTMLVideoElement)) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            video.pause();
            return;
        }

        video.muted = true;
        video.play().catch(() => {});
    })();
</script>
