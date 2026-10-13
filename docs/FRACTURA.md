# Fractura V5 — beta

Restores the original V2 panoramic sorcerer and boss artwork as wide scenes
with soft edge fades. Keeps the V3 Vulcanus scenery and ornate score altars.
Reading chapters now have dark local surfaces instead of exposing the world
behind every paragraph. Faction stories use opaque red/blue shaded panels
with subtle leader silhouettes. Mission tables have solid alternating rows,
larger labels, readable goals and point values. Joining links have contrasting
backgrounds. Decorative horizontal chapter rules are removed.

The panoramic scenes drift gently with live rune rings, fog, light and canvas
particles. The pause control stops all CSS animation and canvas drawing;
reduced-motion and hidden tabs remain supported. Portrait parallax is no longer
used in the opening scene. No map or framed character cards are restored.

Caos / Varkor remains left (Family 2), Nexo / Aldren right (Family 1). The SQL
reader and counters are unchanged: read-only aggregate, exact contribution
digits, 60-second cache, explicit stale/failure fallback. Healthy timestamps
stay hidden. Native mission goals, levels and rewards are preserved; no daily
reset, boss schedule or in-game NPC replacement is promised.

V3 is retained in Git history (4e4c511) for rollback. The deploy guard now checks
both panoramic V2 images alongside the V3 assets. PHP lint, JS score/effects
checks, deployment shell syntax and asset references are checked locally.
Browser layout QA is unavailable here; desktop/mobile framing remains to be
verified on the cPanel beta deployment.

## V5 refinements

Scene edges now fade to transparent over the common Vulcanus background.
The opening overlay no longer terminates with a solid black bottom; the
territory reuses the continuous background instead of starting a new opaque
backdrop. Boss figure and reading aura both fade at the scene boundaries.
Navigation, balance and final enlist shading use radial falloffs.

Affiliation totals have a separate plate between altar supports. Altar artwork
pulses in brightness, saturation and faction glow; numbers remain steady.
The new vector metal/crystal balance frame surrounds an animated segmented
energy bar. Large percentages and a caption identify the proportion of the
combined points. Actual faction shares and zero/unavailable handling remain
unchanged; pause and reduced-motion also cover these new effects.

Mission details, targets and reward tables are removed from this public page.
No server mission configuration or contribution data is modified.
V4 is preserved in Git at 60200b4 for rollback.

## V6 seam correction

Radial chapter shading now uses `ellipse closest-side` and reaches zero alpha
at 100%, rather than still being partially opaque at a rectangular layer edge.
The heading receives the same separate feathered backdrop. Battle shading,
fog and light fade independently at their container boundaries. Both mask
prefixes are kept aligned on the boss aura, including mobile overrides.
Faction panels no longer have an opaque background, shadow or clipped polygon;
each has its own expanded radial reading backdrop and feathered portrait.
All feathering applies to decoration only, preserving sharp text and controls.
V5 is retained at f88f5c5 for rollback. Browser visual QA remains pending.

## V7 balance stacking and atmosphere

The vector frame was above the actual ratio fills: its opaque interior hid
both faction colors while the separate high-z shimmer remained visible.
The track now establishes a stacking context at z-index 3 above the frame at
2, making the real red width and blue remainder visible. Directional energy
textures move inward from both sides; their frontier remains the actual score
boundary. Empty totals remain neutral. Existing pause/reduced-motion rules
also freeze these effects.

Reading clouds behind the factions are expanded on desktop and mobile.
The page wrapper no longer clips vertical decoration at its top boundary;
horizontal overflow is clipped at the body instead. This lets the title cloud
fade above its own container. V6 remains in Git at 61614f4 for rollback.
CSS layer checks and score/motion tests pass; live browser QA remains pending.

The user's pressure effect is a separate decorative element anchored at the
red fill's real endpoint. Red and blue flare lobes approach from opposite sides,
compress and brighten together, then recoil. The score widths never oscillate;
zero-total state hides the clash. It uses the existing pause/reduced-motion
selectors and is aria-hidden.

## V8 native scrolling

The shared template sets `.module-surface { overflow-x:auto }`. Fractura's
full-bleed art and expanded decorative backdrops inherited that internal scroll
container (the other overflow axis computes to auto), enabling lateral panning
and consuming wheel/touch interaction over the score area. The page-specific
module override now explicitly uses overflow:visible, leaving vertical scrolling
to the document. The existing body overflow-x:clip prevents decorative side
bleed from extending the viewport. Page touch-action permits vertical panning
and pinch zoom; no wheel/touch interceptor, synthetic scroll or preventDefault
is introduced. Scores, scenery and effects are unchanged.

V7 is preserved at f8c21a4 for rollback. CSS cascade and code checks pass;
actual wheel/touch behavior on desktop/mobile remains to be verified deployed.

## V9 altar typography

Public member labels now read combatiente/combatientes, for character counts
in the same source (not unique accounts or online players). Both PHP first
render and JS refresh use the same singular/plural wording. Creed, heading,
points and member count are spaced within the dark central stone. The external
pill that covered the lower crest is removed. Small text is larger and the
lower faction link has a contrasting background. V8 is preserved at 1e03e58;
scroll fixes and art effects remain in place. Browser framing QA is pending.

## V10 unified altar inscription

Replaces independently positioned text with one centered flex column within
the stone face: faction name, creed, large score/point label and member count.
The lower link moves below the altar base without a rectangular button.
Two altars stack through 700px so the inscription remains readable. Existing
selectors for exact live counts are preserved, as are pulse and scroll fixes.
V9 is preserved at 8241852. PHP render, JS score/effects and syntax checks pass;
browser visual framing remains pending.
