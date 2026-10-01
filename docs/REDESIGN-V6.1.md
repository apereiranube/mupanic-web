# V6.1 — chapter seams, Dark Lord and audible ambience

User feedback: chapters blend without a boundary; Conquista artwork feels cartoon-like; ambient audio is inaudible on ordinary speakers. The copper metal treatment is retained.

Changes: copper gradient seams with a small central diamond between the scrolling chapters, plus fine metallic boundaries on major scenes. New original Dark Lord fortress artwork matches the hero's material/lighting direction while using a different character, helmet, scepter and burgundy cloak. Headroom accounts for the fixed navigation in desktop/mobile composition. No changes to the hero's character.

Audio: replaces the sub-bass-only drone with filtered mid-range pad tones (147–440 Hz) and soft air, with a volume slider. Starts only after an explicit click. Audio activation is awaited and checked; rejected activation displays an error and keeps the button off. Off suspends the audio context. Hidden tabs suspend audio. No audio files, autoplay or network dependencies.

Validation: PHP and JS syntax, whitespace, five responsive widths, chapter activation, no horizontal overflow, menu/guide paths, missing data/reduced motion/no-JS. Audio instrumented in Chromium: running context, output RMS approximately 0.071, dominant peak approximately 151 Hz; slider zero gives near-silence, off suspends, blocked resume reports error without claiming enabled. These measurements verify signal generation; final loudness also depends on the visitor's system/browser volume.

Only the template overlay and repository documentation/previews changed. Configuration, secrets and deploy scope are untouched. Deploy via cPanel Update from Remote → Deploy HEAD Commit.

## V6.2 correction from user feedback

Removed the synthesized ambience and all audio controls/code: measurable sound did not translate into a suitable game atmosphere. Removed pointer/scroll translation of the hero character/environment and the broad armor light sweep. Hero composition stays stationary; only fog, embers, ambient light and sword aura animate, with the existing pause/reduced-motion controls. Metallic treatment, chapter seams and the distinct Dark Lord artwork remain. Cache version is 6.2.

Targeted Chromium verification compares computed character/environment transforms before and after pointer movement, scroll and pause; all remain unchanged. Confirms audio controls absent, armor overlay removed, atmosphere pauses/resumes, and no runtime errors. PHP/JS syntax and whitespace checks passed. No server configuration/deployment changes.
