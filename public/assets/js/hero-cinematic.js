(() => {
  const hero = document.getElementById('home');
  const video = hero?.querySelector('.hero-video');
  const source = video?.querySelector('source[data-src]');
  if (!hero || !video || !source) return;

  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const smallScreen = matchMedia('(max-width: 767px)');
  const connection = navigator.connection;
  const desktopPoster = video.getAttribute('poster');
  // A retained bitmap avoids Chrome's native video-overlay clearing on scroll.
  // Mobile keeps native inline playback to avoid extra drawing work on phones.
  const surface = document.createElement('canvas');
  surface.className = 'hero-frame';
  surface.setAttribute('aria-hidden', 'true');
  let context = null;
  try { context = surface.getContext('2d', { alpha: false }); } catch (_) { /* Native fallback. */ }
  if (context) video.after(surface);
  const useSurface = () => context && !smallScreen.matches;
  video.defaultMuted = true;
  video.muted = true;
  let failed = false;
  let frameRequest = null;
  let animationRequest = null;
  let lastPaintedTime = -1;
  let mediaVersion = 0;
  // Mobile gets its own smaller video; accessibility and data-saving preferences still apply.
  const posterOnly = () => reducedMotion.matches || connection?.saveData || /(^|-)2g$/.test(connection?.effectiveType || '');
  const showPoster = () => {
    mediaVersion++;
    if (frameRequest !== null) video.cancelVideoFrameCallback?.(frameRequest);
    if (animationRequest !== null) cancelAnimationFrame(animationRequest);
    frameRequest = null;
    animationRequest = null;
    lastPaintedTime = -1;
    video.classList.remove('is-playing');
    surface.classList.remove('is-ready');
  };
  const revealDecodedFrame = () => {
    if (frameRequest !== null || animationRequest !== null) return;
    if (!useSurface() && video.classList.contains('is-playing')) return;
    const version = mediaVersion;
    const reveal = () => {
      if (version !== mediaVersion) return;
      frameRequest = null;
      animationRequest = null;
      if (video.readyState >= 2 && !posterOnly() && !failed) {
        if (useSurface() && video.currentTime !== lastPaintedTime) {
          // Resize only for a new source, never on scroll/viewport-height changes.
          if (surface.width !== video.videoWidth || surface.height !== video.videoHeight) {
            surface.width = video.videoWidth;
            surface.height = video.videoHeight;
          }
          try {
            context.drawImage(video, 0, 0, surface.width, surface.height);
            lastPaintedTime = video.currentTime;
            surface.classList.add('is-ready');
          } catch (_) {
            // If a frame is temporarily unavailable, keep the previous bitmap.
          }
        }
        video.classList.add('is-playing');
      }
      if (useSurface() && !video.paused && !document.hidden && !posterOnly() && !failed) schedule();
    };
    const schedule = () => {
      if (video.requestVideoFrameCallback) frameRequest = video.requestVideoFrameCallback(reveal);
      else animationRequest = requestAnimationFrame(reveal);
    };
    // 'playing' can fire before a frame reaches the compositor on mobile.
    schedule();
  };
  const updatePlayback = () => {
    hero.classList.toggle('has-frame-surface', !!useSurface());
    if (posterOnly() || failed) {
      video.pause();
      showPoster();
      if (source.hasAttribute('src')) { source.removeAttribute('src'); video.load(); }
      return;
    }
    const url = smallScreen.matches ? source.dataset.mobileSrc : source.dataset.src;
    video.poster = smallScreen.matches ? video.dataset.mobilePoster : desktopPoster;
    if (source.getAttribute('src') !== url) {
      showPoster();
      source.src = url;
      video.load();
    }
    if (document.hidden) { video.pause(); return; }
    video.muted = true;
    if (video.paused) video.play().catch((error) => {
      // A pause/source change can cancel a pending play request. Keep its last frame.
      if (error.name !== 'AbortError' && !video.classList.contains('is-playing')) showPoster();
    });
    else revealDecodedFrame();
  };
  video.addEventListener('playing', revealDecodedFrame);
  const onError = () => { failed = true; showPoster(); };
  video.addEventListener('error', onError);
  source.addEventListener('error', onError);
  // Keep the last decoded frame during buffering; never swap images at the loop boundary.
  reducedMotion.addEventListener('change', updatePlayback);
  smallScreen.addEventListener('change', updatePlayback);
  connection?.addEventListener?.('change', updatePlayback);
  document.addEventListener('visibilitychange', updatePlayback);
  window.addEventListener('pageshow', updatePlayback);
  // Some phone browsers require a user gesture before allowing even muted playback.
  hero.addEventListener('pointerup', () => { if (video.paused) updatePlayback(); }, { passive: true });
  // Keep playback continuous while scrolling in this tab: no decoder restart on return.
  // Hidden tabs still pause, retaining the decoded frame and source.
  updatePlayback();
})();
