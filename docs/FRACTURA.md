# Fractura — beta

Route /fractura/; header entry immediately after Mercado.
Custodios del Sello = native Duprian / Family 1; Herederos de la Fractura =
native Vanert / Family 2. These are web identities, not an in-game rename.

Totals use the entire MuOnline43.dbo.Gens_Rank Contribution column per family,
plus affiliated character counts. The reader verifies Name, Family and
Contribution columns before use. SELECT only. Decimal strings preserve all
counter digits; the browser uses BigInt. A successful empty query means zero;
missing data means an unavailable state. Cached fallback is identified.

Cache TTL 60 seconds; stale fallback at most 24 hours; browser polling once
per minute while visible. Points reflect persisted server values, not unsaved
values of connected characters. This introduces no weekly reset, campaign
score, rewards, contribution writes or territory-control mechanics.

The ten independent 50-kill missions reflect the administrator's V3 audit:
minimum levels 240/300/341/361/381, rewards 100/200/500/1000/2000.
The page intentionally makes no promise of daily renewal.

Existing knight-v6.webp, conquest-v61.webp and origin.webp supply artwork;
provenance is in img/SOURCES.md. Illustrated web crests are not in-game items.

Deploy with the current beta cPanel Update from Remote / Deploy HEAD Commit.
Verify the live counters and navigation after deploy. No live hosting config
or credentials enter Git.
