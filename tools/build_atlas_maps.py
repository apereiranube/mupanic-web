"""Convert administrator-supplied client minimaps; never extract executables.

Usage: python tools/build_atlas_maps.py client-maps.zip
Requires Pillow and NumPy. World textures use the client's map-id + 1 convention.
Unverified/custom maps are kept out of the public manifest.
"""
import io
import json
import sys
import zipfile
from pathlib import Path
from PIL import Image
import numpy as np

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'overlay/templates/mupanic/img/atlas/maps'
NAMED = {'Lorencia': 0, 'Dungeon': 1, 'Devias': 2, 'Noria': 3,
         'Losttower': 4, 'Atlans': 7, 'Tarkan': 8, 'Icarus': 10,
         'Aida': 33, 'Crywolf': 34, 'Kantru': 37, 'Kantru3': 39,
         'Barracks': 41, 'Elbeland': 51, 'Calmness': 56, 'Raklion': 57,
         'Kalrutan': 80, 'Kalrutan2': 81, 'LandofTrials': 31,
         'Vulcanus': 63, 'Stadium': 6}

def decode(name, blob):
    offset = 24 if name.lower().endswith('.ozj') else 4
    im = Image.open(io.BytesIO(blob[offset:]))
    im.load()
    return im.convert('RGB' if im.mode != 'RGBA' else 'RGBA')

def main():
    OUT.mkdir(parents=True, exist_ok=True)
    manifest = {}
    with zipfile.ZipFile(sys.argv[1]) as archive:
        names = {n.replace('\\', '/'): n for n in archive.namelist()}
        for label, mid in NAMED.items():
            high = f'Custom/Maps/World{mid+1}.ozj'
            low = f'Custom/Maps/{label}.ozt'
            if low not in names:
                continue
            source = low
            im = decode(low, archive.read(names[low]))
            if high in names:
                candidate = decode(high, archive.read(names[high]))
                # Filename alone is insufficient: some client world images are placeholders.
                a = np.asarray(candidate.convert('L').resize((128,128)), dtype=float).ravel()
                b = np.asarray(im.convert('L').resize((128,128)), dtype=float).ravel()
                if np.std(a) and np.std(b) and np.corrcoef(a,b)[0,1] > .95:
                    im, source = candidate, high
            encoded = io.BytesIO()
            im.save(encoded, 'WEBP', quality=90, method=6)
            blob = encoded.getvalue()
            checked = Image.open(io.BytesIO(blob)); checked.load()
            (OUT / f'{mid}.webp').write_bytes(blob)
            manifest[str(mid)] = {'file': f'img/atlas/maps/{mid}.webp',
                'width': im.width, 'height': im.height, 'source': source,
                'coordinateOverlay': False}
        # Kanturu 2 has a separate world texture, not a renamed Kanturu 1 image.
        source = 'Custom/Maps/World39.ozj'
        if source in names:
            im = decode(source, archive.read(names[source]))
            encoded = io.BytesIO(); im.save(encoded, 'WEBP', quality=90, method=6)
            (OUT / '38.webp').write_bytes(encoded.getvalue())
            manifest['38'] = {'file': 'img/atlas/maps/38.webp',
                'width': im.width, 'height': im.height, 'source': source,
                'coordinateOverlay': False}
    path = ROOT / 'overlay/templates/mupanic/inc/atlas-assets.json'
    path.write_text(json.dumps({'maps': manifest, 'monsters': {}},
                               ensure_ascii=False, indent=2) + '\n')
    print(f'Converted {len(manifest)} maps. Coordinates remain separate until calibrated.')

if __name__ == '__main__':
    main()
