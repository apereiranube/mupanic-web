# Fractura V4 — beta

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
