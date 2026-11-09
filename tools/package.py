#!/usr/bin/env python3
"""Build both Moodle plugin ZIPs, excluding local environments and Git metadata."""
from pathlib import Path
import argparse
import zipfile

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--output', type=Path, default=root / 'dist')
args = parser.parse_args()
args.output.mkdir(parents=True, exist_ok=True)

files = [p for p in root.glob('*.php')]
for dirname in ('backup', 'classes', 'db', 'lang', 'pix', 'tests'):
    files.extend(p for p in (root / dirname).rglob('*') if p.is_file())
files.extend(root / name for name in ('README.md', 'TESTING.md', 'LICENSE'))
with zipfile.ZipFile(args.output / 'qtype_ipv4.zip', 'w', zipfile.ZIP_DEFLATED) as z:
    for p in sorted(files):
        z.write(p, 'ipv4/' + p.relative_to(root).as_posix())
module = root / 'mod' / 'practicum'
with zipfile.ZipFile(args.output / 'mod_practicum.zip', 'w', zipfile.ZIP_DEFLATED) as z:
    for p in sorted(module.rglob('*')):
        if p.is_file():
            z.write(p, 'practicum/' + p.relative_to(module).as_posix())
print(args.output / 'qtype_ipv4.zip')
print(args.output / 'mod_practicum.zip')
