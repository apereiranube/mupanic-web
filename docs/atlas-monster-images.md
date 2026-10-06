# Client monster portraits

88 transparent 384 × 384 WebP portraits are supplied by the MU PANIC client's
BMD models and OZJ/OZT textures. They were rendered locally and visually reviewed;
no AI-generated replacement creatures are used. Total image payload: 1.69 MB,
loaded lazily in map monster cards and boss reward details.

The ID mapping follows client creation functions, rather than assuming monster
ID + 1 equals the model filename. Mapping provenance and source fingerprints are
recorded in `tools/atlas-monster-audit.json`. Rendering uses action 0, frame 0,
orthographic projection and simple diffuse lighting. Particle effects, separate
equipment, level-dependent skins and invisible-body effects require additional
composition or verification and are deliberately excluded.

## Coverage

Of 187 IDs found in the exported map population and special rewards, 88 have
reviewed portraits, 49 require additional composition, 49 have no verified model
mapping, and Selupan lacks the texture `Monster/blast2_r.ozj` in the supplied
packages. Missing portraits keep the existing text card without a substitute.
Classic equipped mobs may require the client's `Data/Item` and `Data/Player`
resources. The audit identifies each pending ID and its reason.

## Rebuild

With Python, numpy, Pillow and scipy installed:

```sh
python tools/build_atlas_monsters.py MOBS_01.zip MOBS_02.zip MOBS_03.zip MOBS_04.zip MOBS_05.zip
```

Use the administrator's original packages. Raw client archives, models and
third-party client source are not deployed. The builder checks model and texture
fingerprints; altered resources must be rendered as candidates and reviewed
before replacing approved images. Existing map manifest entries are preserved.

The VPS's 30-minute synchronization continues updating population and drops by
monster ID. It neither downloads client assets nor overwrites the portrait
manifest. New IDs remain usable as text cards until their images are approved.

## Format and mapping references

- Client creation functions and model enums: https://github.com/sven-n/MuMain
- BMD v12 file decoding: https://github.com/Balgas/muonline/blob/master/MuUnity/Assets/Sources/MuPacket/MuEncDec.cs

The CPU reader validates headers, bounds, triangle indices, frame counts and bone
parents. Only bones referenced by geometry or their ancestors are posed;
unused dummy branches are ignored. Geometry referencing a dummy bone fails
explicitly. These still images do not reproduce the client's particle renderer.


## Public game portraits for reward cards (2026-10-06)

Added 31 locally stored WebP portraits to complete all 34 configured boss/creature reward cards. The original 88 client renders remain intact. These are public MU Online game illustrations, not newly generated artwork or screenshots claimed to come from MU PANIC.

Sources: [MuOnline.Net guides](https://www.muonline.net/guides/) and [Blackrock game guide](https://blackrock.games/index.php?id=guide). Game models belong to Webzen Inc. Visible attribution appears below the reward catalogue. `tools/atlas-web-portrait-sources.json` records each monster ID, exact image URL, source SHA-256, credit, original dimensions and processing. Only the illustration is reused; MU PANIC's names, stats and reward lists still come from its own snapshot.

Files live in `img/atlas/monsters/web/<monster-ID>-<source-hash>.webp`. No remote image requests or base64 deployment are needed. Images retain their native resolution and colours; only transparent padding is trimmed and the file is encoded as lossless WebP. Several golden creatures are clean game screenshots rather than transparent renders. Maya's left/right cards use the source guide's shared hand illustration, retaining their distinct configured names and rewards; these do not claim side-specific screenshots.

Reward portraits load eagerly with low priority to avoid hidden-section lazy-loading delays without competing with initial page artwork. The common charcoal frame accommodates both transparent renders and game backgrounds. `scripts/deploy-beta.sh` copies the binary assets with the entire template directory. Rebuilding the original BMD portraits preserves these additional manifest entries because their audit status is not `rendered`.
