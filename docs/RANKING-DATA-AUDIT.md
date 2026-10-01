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

Resets/Master Resets activation is pending the database audit and live CMS mapping. No unavailable or unverified category has been exposed as a working ranking.
