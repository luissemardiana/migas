# Cinematic homepage hero

Footage: [Aerial View of Oil Refinery Plant](https://www.pexels.com/video/aerial-view-of-oil-refinery-plant-10396407/) by Tom Fisk, Pexels. [License](https://www.pexels.com/license/). Existing project source and permission record: `../images/refinery-plant-source.md`.

This is real refinery footage already used by the project, not generated video or placeholder media. It is decorative and does not represent a facility owned by MIGAS Training.

`refinery-cinematic.mp4`: 1920 × 1080, 16:9, 25 fps, 8 seconds, H.264/yuv420p, no audio, fast-start MP4. Derived from the project's existing `plant-loop-smooth.mp4` master: original 4K footage slowed to half speed with the end blended into the beginning and temporal smoothing around the seam. Re-encoded at CRF 23 with a 5 Mbps ceiling. Camera direction is not reversed. No CSS zoom, end-frame replacement, or per-loop JavaScript restart.

`refinery-cinematic-mobile.mp4`: portrait crop, 720 × 960, 25 fps, 8 seconds, H.264/yuv420p, no audio, fast-start MP4, approximately 1.66 MB. Derived from the desktop loop, using the same crop as the mobile poster. Screens up to 767 px load only this smaller video; wider screens use the desktop asset.

Posters are decoded from the same footage: desktop 1920 × 1080 and mobile crop 720 × 960. Reduced-motion/data-saving preferences, disabled JavaScript, loading failures, and blocked autoplay retain the poster. Tiny inline JPEG previews in the hero stylesheet keep refinery imagery visible before external image downloads finish. The video is revealed only after a decoded frame is ready for presentation.

Browser-native muted inline playback loops both videos. Playback continues while scrolling within the page to avoid pause/resume flicker; it pauses when the tab is hidden while retaining the last frame. Returning to the tab or a browser-restored page resumes without reloading the source or repeating the initial fade. A touch on the hero also retries playback if the phone requires a user gesture.

Desktop presentation uses an opaque retained canvas fed by the HTML5 video's decoded-frame callbacks. It preserves the last painted refinery frame if video presentation stalls or Chrome rebuilds its video overlay while scrolling. The canvas uses the source dimensions and is never resized during scrolling. No opacity/fade transition is used for the video or canvas. Mobile uses native inline video; if a canvas context is unavailable on desktop, native playback is used as well. Posters and inline previews remain underneath both paths.
