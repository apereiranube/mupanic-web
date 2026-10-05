"""Rebuild reviewed client portraits; new IDs remain pending review.

Usage: python tools/build_atlas_monsters.py MOBS_01.zip ... MOBS_05.zip
Requires numpy, Pillow and scipy. Never reads server tokens or runs game code.
"""
import argparse
import hashlib
import json
from pathlib import Path

from atlas_bmd import archive_assets, parse
from atlas_bmd_render import render

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / 'overlay/templates/mupanic'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archives', nargs='+', type=Path)
    args = parser.parse_args()
    assets = archive_assets(args.archives)
    audit_path = ROOT / 'tools/atlas-monster-audit.json'
    audit = json.loads(audit_path.read_text())
    manifest_path = WEB / 'inc/atlas-assets.json'
    manifest = json.loads(manifest_path.read_text())
    output = WEB / 'img/atlas/monsters'
    output.mkdir(parents=True, exist_ok=True)
    changed = 0
    for mid, row in audit.items():
        if row['status'] != 'rendered':
            continue
        key = row['modelFile']
        # Every source involved in the reviewed portrait must be identical.
        # A changed client requires visual review before replacing a public image.
        if hashlib.sha256(assets[key]).hexdigest() != row['sourceHash']:
            raise ValueError(f'Monster {mid}: model changed; review a new candidate first')
        for texture, digest in row['textureHashes'].items():
            if hashlib.sha256(assets[texture]).hexdigest() != digest:
                raise ValueError(f'Monster {mid}: texture changed; review required')
        render(parse(assets[key]), assets, 'monster', output / f'{mid}.webp',
               yaw=row['yaw'], elevation=row['elevation'], size=768,
               frame=row['frame'])
        manifest['monsters'][mid] = {
            'file': f'img/atlas/monsters/{mid}.webp', 'width': 384,
            'height': 384, 'sourceHash': row['sourceHash'],
        }
        changed += 1
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')
    print(f'Rebuilt {changed} reviewed portraits. Map assets preserved.')


if __name__ == '__main__':
    main()
