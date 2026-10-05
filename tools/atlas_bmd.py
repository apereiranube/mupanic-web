"""Read administrator-supplied BMD v10/v12 geometry. No client executables run."""
import struct, io, zipfile, json
from pathlib import Path
import numpy as np

class Reader:

    def __init__(self, data):
        self.data = data
        self.pos = 0

    def take(self, n):
        if self.pos + n > len(self.data):
            raise ValueError('Truncated BMD')
        out = self.data[self.pos:self.pos + n]
        self.pos += n
        return out

    def unpack(self, fmt):
        return struct.unpack(fmt, self.take(struct.calcsize(fmt)))

    def string(self, n):
        return self.take(n).split(b'\x00')[0].decode('ascii', errors='replace')

def parse(data):
    r = Reader(data)
    if r.take(3) != b'BMD':
        raise ValueError('Not BMD')
    version = r.unpack('<B')[0]
    if version == 12:
        length = r.unpack('<I')[0]
        if length != len(data) - 8:
            raise ValueError('Invalid encrypted BMD length')
        encrypted = np.frombuffer(r.take(length), dtype=np.uint8).astype(np.int16)
        key = np.array([209, 115, 82, 246, 210, 154, 203, 39, 62, 175, 89, 49, 55, 179, 231, 162], dtype=np.int16)
        previous = np.empty(length, dtype=np.int16)
        previous[0] = 94
        previous[1:] = encrypted[:-1] + 61 & 255
        decoded = (encrypted ^ np.resize(key, length)) - previous & 255
        r = Reader(decoded.astype(np.uint8).tobytes())
    elif version != 10:
        raise ValueError('Unsupported BMD version ' + str(version))
    name = r.string(32)
    nm, nb, na = r.unpack('<3h')
    if not (0 < nm <= 50 and 0 < nb <= 200 and (0 < na <= 200)):
        raise ValueError('Invalid BMD header')
    meshes = []
    for i in range(nm):
        nv, nn, nt, nf, texture = r.unpack('<5h')
        if not all((0 <= x <= 30000 for x in (nv, nn, nt, nf))):
            raise ValueError('Invalid mesh counts')
        verts = np.frombuffer(r.take(nv * 16), dtype=np.dtype({'names': ['bone', 'position'], 'formats': ['<i2', ('<f4', 3)], 'offsets': [0, 4], 'itemsize': 16})).copy()
        normals = np.frombuffer(r.take(nn * 20), dtype=np.dtype({'names': ['bone', 'normal'], 'formats': ['<i2', ('<f4', 3)], 'offsets': [0, 4], 'itemsize': 20})).copy()
        uv = np.frombuffer(r.take(nt * 8), dtype='<f4').reshape(-1, 2).copy()
        faces = np.frombuffer(r.take(nf * 64), dtype=np.dtype({'names': ['polygon', 'vertices', 'normals', 'uv'], 'formats': ['i1', ('<i2', 4), ('<i2', 4), ('<i2', 4)], 'offsets': [0, 2, 10, 18], 'itemsize': 64})).copy()
        filename = r.string(32)
        if any((x['polygon'] != 3 for x in faces)):
            raise ValueError('Nontriangular mesh')
        if not filename or not all(((faces[k][:, :3] >= 0).all() and (faces[k][:, :3] < limit).all() for k, limit in [('vertices', nv), ('normals', nn), ('uv', nt)])):
            raise ValueError('Invalid mesh indices or texture')
        meshes.append(dict(vertices=verts, normals=normals, uv=uv, faces=faces, texture=filename))
    actions = []
    for i in range(na):
        keys, lock = r.unpack('<hB')
        actions.append(keys)
        if keys < 1 or keys > 10000:
            raise ValueError('Invalid frame count')
        if lock:
            r.take(keys * 12)
    bones = []
    for i in range(nb):
        dummy = r.unpack('<B')[0]
        if dummy:
            bones.append(None)
            continue
        name = r.string(32)
        parent = r.unpack('<h')[0]
        frames = []
        if parent >= i or parent < -1:
            raise ValueError('Invalid parent')
        for keys in actions:
            positions = np.frombuffer(r.take(keys * 12), '<f4').reshape(-1, 3).copy()
            rotations = np.frombuffer(r.take(keys * 12), '<f4').reshape(-1, 3).copy()
            frames.append((positions, rotations))
        bones.append(dict(name=name, parent=parent, frames=frames))
    if r.pos != len(r.data) and (not (version == 12 and len(r.data) - r.pos == 8)):
        raise ValueError(f'Trailing data {len(r.data) - r.pos}')
    return dict(version=version, name=name, meshes=meshes, bones=bones, actions=actions)

def archive_assets(paths):
    assets = {}
    for p in paths:
        with zipfile.ZipFile(p) as z:
            for n in z.namelist():
                if not n.endswith('/'):
                    key = n.replace('\\', '/').lower()
                    if key in assets:
                        raise ValueError('Duplicate asset ' + key)
                    assets[key] = z.read(n)
    return assets
