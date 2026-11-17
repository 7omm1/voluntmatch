/*
 * VoluntMatch — Slider del hero
 */
/* ============================================================
   HERO SLIDER
============================================================ */
const heroSlides = [...document.querySelectorAll('[data-hero-slide]')];
const heroDots = [...document.querySelectorAll('[data-hero-dot]')];
const heroVideo = document.querySelector('[data-hero-video]');
const heroPlay = document.querySelector('[data-hero-play]');
const heroCounter = document.querySelector('[data-hero-counter]');

if (heroSlides.length > 0) {
    let slideIndex = 0;
    let playing = true;
    let timer = null;

    const showSlide = (index) => {
        slideIndex = (index + heroSlides.length) % heroSlides.length;
        heroSlides.forEach((el, i) => el.classList.toggle('active', i === slideIndex));
        heroDots.forEach((el, i) => el.classList.toggle('active', i === slideIndex));
        if (heroCounter) {
            heroCounter.textContent = `0${slideIndex + 1} / 0${heroSlides.length}`;
        }
        if (heroVideo) {
            const current = heroSlides[slideIndex];
            heroVideo.poster = current.dataset.poster || heroVideo.poster;
            if (heroVideo.currentSrc !== current.dataset.video) {
                heroVideo.src = current.dataset.video;
                heroVideo.load();
                if (playing) heroVideo.play().catch(() => {});
            }
        }
    };

    const stopTimer = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const startTimer = () => {
        stopTimer();
        timer = window.setInterval(() => showSlide(slideIndex + 1), 8500);
    };

    heroDots.forEach((dot, index) => {
        dot.addEventListener('click', () => showSlide(index));
    });

    if (heroPlay) {
        heroPlay.addEventListener('click', () => {
            playing = !playing;
            const iconPause = heroPlay.querySelector('.icon-pause');
            const iconPlay = heroPlay.querySelector('.icon-play');
            if (iconPause) iconPause.hidden = !playing;
            if (iconPlay) iconPlay.hidden = playing;
            if (playing) {
                heroVideo?.play().catch(() => {});
                startTimer();
            } else {
                heroVideo?.pause();
                stopTimer();
            }
        });
        const iconPlay = heroPlay.querySelector('.icon-play');
        if (iconPlay) iconPlay.hidden = true;
        startTimer();
    }
}
