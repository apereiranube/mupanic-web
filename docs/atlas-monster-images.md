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
