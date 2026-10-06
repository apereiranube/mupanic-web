# Atlas presentation — beta, 6 October 2026

The Atlas has its own complete charcoal/copper stylesheet. The retired Atlas, workshop and monster-dialog rules have been removed from the shared stylesheet. Map population, drops, account rates, crafting recipes, reward lists and their identifiers remain supplied by the existing runtime snapshot. Main is not deployed or updated by this change; beta's cPanel task stays `scripts/deploy-beta.sh`.

The cartographic hero, four destination cards, terrain cards, expedition inspector, portrait reward cards, object finder, crafting panel and progression references share one typography and surface system. Reward categories filter the existing 171 associations. Native details retain large reward lists in page flow; compact event dossiers use a native dialog with keyboard focus, Escape, backdrop close, background scroll locking and restored trigger focus. Without JavaScript, the original event articles and all chapters are available.

A small first-paint bootstrap selects the initial chapter before the late snapshot/JavaScript initializes. It matches maps, spots, mobs, reward, recipe, equipment and event deep links. If initialization fails, a five-second fallback restores the complete readable guide. The redundant introductory header remains removed.

## Events and verified scope

Administrator export `MU_PANIC_EVENTOS_20261006_105011.zip` supplied on 6 October contains 198 event/reward files. The allowlisted event parser finds 37 core catalogue entries, 32 enabled: classic events, custom contests, conditional boss instances, nine scheduled invasions and six staff events. Disabled entries stay out of the active catalogue. Silver Invasions has a definition but no matching schedule and is not announced as scheduled. Eight CustomArena entries and Lluvia de Premios are also parsed, but their activation file is absent from this ZIP, so their presence alone does not mark them active. The first VPS upload reads the two allowlisted switches from `GameServerInfo - Custom.dat` and automatically adds them if enabled, without another ZIP.

`public-events.json` contains only public activation, schedule patterns (including wildcard hours and Windows weekdays), configured durations, reward IDs/item summaries/coin values, map and monster IDs. Original ZIP/configuration, addresses, credentials and processes are never published. Caza del Maldito is the administrator-confirmed public alias for Pandora; aliases and explanations are editorial, activation/times/rewards are configuration. These flags indicate enabled configuration, not a live GameServer state or a successfully reloaded configuration.

`sync_atlas_events.py` independently publishes event data using the existing private token and signed HTTPS protocol. The beta receiver validates schema, bounds, IDs, strings and freshness, then replaces only its event snapshot atomically. A failed export/upload retains the last published catalogue. This is deliberately separate from the existing production balance receiver; main is not modified and its drop sync URL/payload stay compatible.

After beta cPanel deploy, run `tools/update_atlas_events_sync.ps1` once on the VPS (prefer the final immutable beta SHA). It validates/downloads two Python files, backs up the existing wrapper, performs a confirmed initial beta upload, then appends event sync to the existing task wrapper. Its schedule stays at 30 minutes, its balance command stays unchanged, and subsequent activation/times/reward changes need no ZIP or website deploy. Browser agenda polls the public beta endpoint every minute, defers replacement while a dossier is open, and preserves the last UI on fetch failure. The polling updates the published catalogue; it does not make the VPS task run faster. VPS installation remains pending until the administrator runs the updater and confirms output.

## Artwork

Built-in imagegen produced six original images (1536×1024), stored as high-quality WebP at `overlay/templates/mupanic/img/atlas/editorial/`. No text, UI, logos or copied client screens are embedded. Existing genuine terrain and monster resources remain unchanged. Event artwork is identified as conceptual in dossiers.

Prompt set: original cinematic medieval dark fantasy in charcoal, burnished copper, antique gold and restrained crimson; detailed materials, dramatic warm volumetric lighting; landscape 3:2, central/right subject with dark negative space; no text, numbers, brands, interface, watermarks or copied game assets.

- `atlas-chamber.webp`: monumental stone table with unlabelled bronze relief terrain, miniature citadels, mountains, copper route markers, brass astrolabe and compass in a smoky vaulted chamber; left side reserved for HTML title.
- `blood-castle.webp`: bridge above an abyss leading to a besieged gothic fortress, ceremonial broken gate, fog and embers.
- `devil-square.webp`: ancient combat arena surrounded by gargoyles and gates, crimson energy and overhead amber light.
- `pandora.webp`: copper/obsidian reliquary chest, ruined courtyard, restrained crimson magical tendrils and jewel glints.

Validation uses the actual guide, header, navigation, portrait assets and current snapshot via PHP fixture. CI checks desktop/tablet/mobile, chapter navigation, filters, map selection, account rates, reward set search, event closing/focus, modal fit, reward links across filters, workshop, no-JS fallback, image paths and page overflow. Shared-style regressions cover Systems, Rankings and Profiles.

Additional original concept art: `pvp-arena.webp` depicts a dark copper dueling arena; `imperial-temple.webp` depicts an obsidian temple with an amber relic. No embedded text or UI.

CustomArena/CustomEventDrop activation and table interpretation follow Louis UP42 documentation (`https://www.jogandomu.com.br/louisup42/`); actual exported column counts are checked. The whole Custom.dat is never published; only the two activation switches influence catalogue output.
