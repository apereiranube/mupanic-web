# Ranking data audit

The website currently uses WebEngine 1.2.7 ranking caches. Adding a category requires verifying both its underlying counter and the deployed CMS column mapping. `MuOnline43` is the active physical database; the CMS connection alias `MuOnline` must not be mistaken for the retired physical database of that name.

## Evidence available on 2026-10-01

- WebEngine supports resets (`rankings_resets.cache`) and grand resets (`rankings_gr.cache`); the latter can represent Master Resets only after checking the live column mapping.
- The supplied balance configuration includes F8 ranking entries for Blood Castle and Devil Square points. This establishes configured UI categories, not persisted SQL records.
- Custom Achievements lists completed Blood Castle, Devil Square and Chaos Castle, PvP, self-defense kills, PK kills and duel wins. Monster achievements include specific monster IDs. These may be packed or capped achievement progress, not independent lifetime counters.
- The latest supplied GameServer Common configuration enables event logging and duels. Enabled logging does not establish a structured winner/boss ledger.
- The existing web PK ranking uses the character PK count. Do not label it as duel wins or all PvP wins.

## Required next evidence

Run `tools/audit_rankings.ps1` on the Windows VPS. It discovers local SQL instances or accepts `-SqlServer`, connects only to `MuOnline43`, reads candidate schema metadata and numeric aggregate samples capped at 5000 rows per metric, and exports a JSON to the Desktop. Windows authentication is tried first. A SQL credential prompt, if required, stays local and credentials are never exported. No names, account contents, inventories, balances or binary payloads are exported.

After confirming candidate counters, compare their values before and after one completed event, boss kill or duel to verify update behavior and meaning. A zero/absent sample is not proof a counter does not exist. Packed achievements require verified decoding and reset/cap semantics. Publish each event separately unless a shared scoring rule is explicitly defined.

## Live database audit received

The administrator supplied `MU_PANIC_RANKINGS_AUDIT_20261001_152459.json` (103 KB). It confirms `dbo.Character.ResetCount` (4 positive records, max 20) and `MasterResetCount` (4, max 4). `RankingBloodCastle.Score` has 2 positive records (max 26000), `RankingDevilSquare.Score` has 2 (max 12030), and `RankingDuel.WinScore/LoseScore` have 2 records with one positive win and one positive loss. These are existing stored counters; the audit alone does not verify continuing writes.

The beta template adds Resets, Master Resets, Blood Castle, Devil Square and Duelos. New views use fixed audited identifiers in the explicitly qualified `MuOnline43` database via the existing CMS `MuOnline` connection alias. They respect the global ranking active switch, configured excluded characters and result limit (capped at 100), use parameterized exclusions, deterministic name tie breakers, and do not alter live CMS configuration. Native rankings retain their CMS handler. Public result snapshots cache for five minutes under separate cache names, keyed by the exclusion/limit policy; a failed refresh can retain a clearly labelled snapshot for up to one day. No cron or VPS change is required. An empty result is different from a database failure.

Other event rankings (Chaos Castle, Castle Siege, PvP Championship, Battle Royale, Demon Guardian, etc.) have zero rows and are not exposed. `Character.Kills` has values but its PvP semantics are not established. Achievements use separate Count/Level columns, not a packed binary field, but monster counter caps/resets and lifetime meaning remain unverified. No general boss leaderboard has been verified. Weekly score columns exist but their reset schedule has not been established, so the new event views use total counters only.

Validation uses PHP rendering with a stub CMS/SQL connection and DOM tests for all five categories, native menu preservation, parameterized exclusions, empty/failure states, cache reuse and stale fallback, plus existing ranking interaction checks. A live beta query is still required after cPanel deployment.
