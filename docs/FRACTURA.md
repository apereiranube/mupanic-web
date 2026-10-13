# Fractura V3 — beta

A full-width, continuous Vulcanus scenery replaces framed key-art and map UI.
Varkor / Caos stays on the left (native Family 2), Aldren / Nexo on the right
(native Family 1). No SQL change, affiliation action or in-game NPC replacement
is included. Public text uses only the approved lore names and point labels;
legacy Gens names, brand repetition and healthy-state polling timestamps are
removed. An actual data outage still has a minimal visible status.

The unchanged read-only score data source preserves all contribution digits,
60-second cache, BigInt updates and explicit stale/failure fallback. Point-
updates can pulse, but never fake extra score. Native missions keep the audited
50-kill goals, levels and rewards. No daily/weekly promise or boss schedule.

All V3 art uses new built-in imagegen layers: two transparent leaders from the
user's NPC reference, the actual boss model as a transparent character,
Vulcanus panorama and paired transparent altar backplates. Source prompts and
alpha verification are in img/fractura/SOURCES.md. No top-down map is displayed.

Visible effects: world drift, separate character floating/parallax, animated
rune rings, light/fault pulses, moving fog, altar light and canvas particles.
One pause control stops all CSS animation, canvas drawing and pointer updates.
Reduced-motion is respected, hidden tabs stop the drawing and polling loops.
The generated character assets preserve their full silhouette and transparency.

PHP render checks cover absence of legacy terms/map/timestamps and new assets.
JS tests cover faction mapping, exact counters, hidden healthy status, pause/
resume, canvas stop/start and reduced-motion. Browser layout QA is unavailable
in this environment; verify desktop and mobile on the cPanel beta deployment.
