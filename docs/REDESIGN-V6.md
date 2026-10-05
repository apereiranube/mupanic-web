# MU PANIC V6 — cinematic scene and interactive server HUD

Builds on V5 navigation and editorial structure. Changes are restricted to the template overlay plus repository documentation/previews. Deploy script is unchanged: template only, no configuration or server files.

- New transparent Dark Knight concept art, sharp armor and sword detail, over the existing Devias environment. Independent pointer/scroll depth, sword glow, armor lighting, fog and embers. Mobile uses a deliberate composition and avoids pointer parallax. Scene pause affects ambient CSS animation; reduced-motion preference disables movement.
- Angular metal-colored account/download buttons, original SVG sword/wing/crest/jewel/party symbols, vertical journey progress on desktop and horizontal on mobile. Chapter color ambience follows the scroll chapter.
- Native keyboard-accessible disclosures for Season, EXP, Master EXP, Drop and Conectados. Guide links retain exact section targets. Works without JavaScript.
- Online count remains sourced from `server_info.cache`; date is its actual first-line timestamp. Optional names use existing `online_characters.cache`, independently of the count, with validation and a 24-name display limit. No added rankings, guessed players, capacity or invented health status.
- Refresh fetches the rendered home on the same origin, never a database/API route. Manual button plus 60-second interval while visible and online, 12-second timeout, preserved last data on failure. This refresh cannot make the CMS cron cache newer. List availability does not imply it shares the count timestamp.
- Quiet synthesized ambient drone is off by default and starts only on click; stops when disabled and suspends while the tab is hidden. No autoplay, tracking or external sound assets.

Validation: PHP/JS syntax, diff whitespace; Chromium at 1440/1024/768/390/320 with no overflow, all chapters, guide/menu/account paths, missing cache, reduced motion and no-JS. Interaction checks cover image decode, pointer depth, pause/resume, sound opt-in, keyboard disclosures, character sanitization, changed count, failed refresh preserving last count, and no-JS HUD. Test data is a local stub; real server cron/list availability is verified after deployment.

Deployment: cPanel Git Version Control, MU PANIC Web → Update from Remote → Deploy HEAD Commit. No cPanel connector/session is available in this workspace; branch publication and actual live deployment are distinct steps.
