# Atlas presentation — beta, 6 October 2026

The Atlas has its own complete charcoal/copper stylesheet. The retired Atlas, workshop and monster-dialog rules have been removed from the shared stylesheet. Map population, drops, account rates, crafting recipes, reward lists and their identifiers remain supplied by the existing runtime snapshot. Main is not deployed or updated by this change; beta's cPanel task stays `scripts/deploy-beta.sh`.

The cartographic hero, four destination cards, terrain cards, expedition inspector, portrait reward cards, object finder, crafting panel and progression references share one typography and surface system. Reward categories filter the existing 171 associations. Native details retain large reward lists in page flow; compact event dossiers use a native dialog with keyboard focus, Escape, backdrop close, background scroll locking and restored trigger focus. Without JavaScript, the original event articles and all chapters are available.

A small first-paint bootstrap selects the initial chapter before the late snapshot/JavaScript initializes. It matches maps, spots, mobs, reward, recipe, equipment and event deep links. If initialization fails, a five-second fallback restores the complete readable guide. The redundant introductory header remains removed.

## Events and verified scope

Pandora V8 installation was reported successful on 6 October: 19:15/22:15, five minutes, two Bless, two Soul, one Chaos and a 50% possibility of Life. These confirmed values are editorial content, not derived from the older bag 163 snapshot. Blood Castle and Devil Square rewards are read from the current runtime bags (12–19 and 145–151), with direct links to each category. Their schedules and activation flags are deliberately not assumed: the supplied gameplay export did not contain `Data/Event` or `GameServerInfo - Event.dat`.

`tools/collect_atlas_events.ps1` gathers those gameplay files and reward bags read-only. No original configuration is committed, no token/SQL/executable is collected, no game process is touched. The complete active-event catalogue and remaining schedules require that current export. Generic mechanics are editorial explanations; category requirements must be checked in this client's entry interface.

## Artwork

Built-in imagegen produced four original images (1536×1024), stored as high-quality WebP at `overlay/templates/mupanic/img/atlas/editorial/`. No text, UI, logos or copied client screens are embedded. Existing genuine terrain and monster resources remain unchanged. Event artwork is identified as conceptual in dossiers.

Prompt set: original cinematic medieval dark fantasy in charcoal, burnished copper, antique gold and restrained crimson; detailed materials, dramatic warm volumetric lighting; landscape 3:2, central/right subject with dark negative space; no text, numbers, brands, interface, watermarks or copied game assets.

- `atlas-chamber.webp`: monumental stone table with unlabelled bronze relief terrain, miniature citadels, mountains, copper route markers, brass astrolabe and compass in a smoky vaulted chamber; left side reserved for HTML title.
- `blood-castle.webp`: bridge above an abyss leading to a besieged gothic fortress, ceremonial broken gate, fog and embers.
- `devil-square.webp`: ancient combat arena surrounded by gargoyles and gates, crimson energy and overhead amber light.
- `pandora.webp`: copper/obsidian reliquary chest, ruined courtyard, restrained crimson magical tendrils and jewel glints.

Validation uses the actual guide, header, navigation, portrait assets and current snapshot via PHP fixture. CI checks desktop/tablet/mobile, chapter navigation, filters, map selection, account rates, reward set search, event closing/focus, modal fit, reward links across filters, workshop, no-JS fallback, image paths and page overflow. Shared-style regressions cover Systems, Rankings and Profiles.
