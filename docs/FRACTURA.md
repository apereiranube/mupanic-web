# Fractura V2 — beta

Route /fractura/, menu immediately after Mercado. User-approved identities:
Caos (Vanert / Family 2), led by Varkor, appears left; Nexo (Duprian / Family 1),
led by Aldren, appears right. Native family IDs and SQL contribution are unchanged.
These are web identities; no in-game NPC replacement is included in this web change.

The score reader remains SELECT-only and aggregates the entire native table,
with decimal-string totals, 60-second cache and explicit stale/unavailable states.
The client updates once per minute while visible, using BigInt. Zero requires
successful empty data. This adds no weekly resets or campaign points.

New artwork was generated with the built-in image tool from the user-selected
NPC reference. Boss artwork is based on a CPU render of the actual administrator-
supplied LordOfDarkness.bmd (class 625), not an invented boss. Prompts and asset
provenance are in img/fractura/SOURCES.md. The map is the actual client OZJ,
losslessly converted to WebP; no speculative boss/spawn pins or new zones.

Boss combat is in beta testing; spawn schedule and rewards are not confirmed,
and the page describes them as forthcoming. Native ten 50-kill missions keep
V3-audited levels and rewards; daily renewal is intentionally not promised.
Membership is described as in-game interaction from level 50. Web CTAs scroll
to instructions, never claim to affiliate a character.

Dark page/background and explicit heading colors override the CMS's pale inner-
page defaults. Map has a native dialog with zoom; without JS it opens the image.
Effects honor prefers-reduced-motion and the user pause setting.

Deploy using beta cPanel Update from Remote / Deploy HEAD Commit. Verify live
counter-family mapping and desktop/mobile layout on hosting after deploy.
