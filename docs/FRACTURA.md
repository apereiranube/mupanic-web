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
