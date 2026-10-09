"""CPU renderer for administrator-supplied MU BMD v10/v12 models (not AI imagery)."""
from atlas_bmd import parse, archive_assets
from pathlib import Path
from PIL import Image
from scipy.spatial.transform import Rotation
import io, numpy as np, json, math

def texture(assets, folder, name):
    p = Path(name)
    suffix = {'.jpg': '.ozj', '.jpeg': '.ozj', '.tga': '.ozt'}.get(p.suffix.lower(), p.suffix.lower())
    key = (folder + '/' + p.stem + suffix).lower()
    if key not in assets:
        raise ValueError('Missing texture ' + key)
    blob = assets[key]
    offset = 24 if suffix == '.ozj' else 4 if suffix == '.ozt' else 0
    im = Image.open(io.BytesIO(blob[offset:]))
    im.load()
    return (np.asarray(im.convert('RGBA')), key)

def posed(model, action=0, frame=0, hidden=()):
    required = set((int(x) for mesh in model['meshes'] for field in ('vertices', 'normals') for x in mesh[field]['bone']))
    for node in list(required):
        while node >= 0:
            if node >= len(model['bones']) or model['bones'][node] is None:
                raise ValueError('Geometry depends on a dummy or invalid bone')
            required.add(node)
            node = model['bones'][node]['parent']
    transforms = []
    for index, bone in enumerate(model['bones']):
        if index not in required:
            transforms.append(None)
            continue
        pos, rot = bone['frames'][action]
        f = min(frame, len(pos) - 1)
        local = np.eye(4)
        local[:3, :3] = Rotation.from_euler('xyz', rot[f]).as_matrix()
        local[:3, 3] = pos[f]
        parent = bone['parent']
        transforms.append(local if parent == -1 else transforms[parent] @ local)
    out = []
    for i, m in enumerate(model['meshes']):
        if i in hidden:
            continue
        v = m['vertices']
        n = m['normals']
        vv = []
        nn = []
        for p in v:
            mat = transforms[int(p['bone'])]
            vv.append(mat[:3, :3] @ p['position'] + mat[:3, 3])
        for p in n:
            mat = transforms[int(p['bone'])]
            nn.append(mat[:3, :3] @ p['normal'])
        out.append(dict(m, points=np.array(vv), directions=np.array(nn)))
    return out

def render(model, assets, folder, dest, hidden=(), yaw=30, elevation=18, size=512, flip_v=False, frame=0, output_size=384):
    meshes = posed(model, frame=frame, hidden=hidden)
    eye = np.array([math.sin(math.radians(yaw)), -math.cos(math.radians(yaw)), math.tan(math.radians(elevation))])
    eye /= np.linalg.norm(eye)
    right = np.cross(eye, [0, 0, 1])
    right /= np.linalg.norm(right)
    up = np.cross(right, eye)
    view = np.vstack([right, up, eye])
    allpoints = np.concatenate([m['points'] for m in meshes]) @ view.T
    low = allpoints[:, :2].min(0)
    high = allpoints[:, :2].max(0)
    center = (low + high) / 2
    scale = size * 0.82 / max(high - low)
    rgba = np.zeros((size, size, 4), dtype=np.uint8)
    depth = np.full((size, size), -np.inf)
    used = []
    keylight = np.array([-0.4, -0.7, 1.0])
    keylight /= np.linalg.norm(keylight)
    for mesh in meshes:
        tex, source = texture(assets, folder, mesh['texture'])
        used.append(source)
        h, w = tex.shape[:2]
        points = mesh['points'] @ view.T
        points[:, :2] = (points[:, :2] - center) * scale + size / 2
        points[:, 1] = size - points[:, 1]
        normals = mesh['directions']
        normals /= np.maximum(np.linalg.norm(normals, axis=1, keepdims=True), 1e-09)
        shade = np.clip(0.68 + 0.32 * np.maximum(normals @ keylight, 0), 0.45, 1.0)
        for face in mesh['faces']:
            inds = face['vertices'][:3]
            tri = points[inds]
            uv = mesh['uv'][face['uv'][:3]]
            light = shade[face['normals'][:3]]
            x0 = max(0, int(np.floor(tri[:, 0].min())))
            x1 = min(size - 1, int(np.ceil(tri[:, 0].max())))
            y0 = max(0, int(np.floor(tri[:, 1].min())))
            y1 = min(size - 1, int(np.ceil(tri[:, 1].max())))
            if x0 > x1 or y0 > y1:
                continue
            a, b, c = tri
            den = (b[1] - c[1]) * (a[0] - c[0]) + (c[0] - b[0]) * (a[1] - c[1])
            if abs(den) < 1e-08:
                continue
            yy, xx = np.mgrid[y0:y1 + 1, x0:x1 + 1]
            xx = xx + 0.5
            yy = yy + 0.5
            ba = ((b[1] - c[1]) * (xx - c[0]) + (c[0] - b[0]) * (yy - c[1])) / den
            bb = ((c[1] - a[1]) * (xx - c[0]) + (a[0] - c[0]) * (yy - c[1])) / den
            bc = 1 - ba - bb
            zz = ba * a[2] + bb * b[2] + bc * c[2]
            inside = (ba >= -1e-07) & (bb >= -1e-07) & (bc >= -1e-07) & (zz > depth[y0:y1 + 1, x0:x1 + 1])
            if not inside.any():
                continue
            coords = ba[..., None] * uv[0] + bb[..., None] * uv[1] + bc[..., None] * uv[2]
            if flip_v:
                coords[..., 1] = 1 - coords[..., 1]
            tx = np.clip((coords[..., 0] * w).astype(int), 0, w - 1)
            ty = np.clip((coords[..., 1] * h).astype(int), 0, h - 1)
            pixels = tex[ty, tx].copy()
            inside &= pixels[..., 3] >= 100
            lighting = ba * light[0] + bb * light[1] + bc * light[2]
            pixels[..., :3] = (pixels[..., :3].astype(float) * lighting[..., None]).clip(0, 255).astype(np.uint8)
            rgba[y0:y1 + 1, x0:x1 + 1][inside] = pixels[inside]
            depth[y0:y1 + 1, x0:x1 + 1][inside] = zz[inside]
    im = Image.fromarray(rgba).resize((output_size, output_size), Image.Resampling.LANCZOS)
    im.save(dest)
    if np.count_nonzero(rgba[..., 3]) < size * size * 0.015:
        raise ValueError('Empty render')
    return dict(modelVersion=model['version'], textures=used, action=0, frame=frame, yaw=yaw, elevation=elevation)
