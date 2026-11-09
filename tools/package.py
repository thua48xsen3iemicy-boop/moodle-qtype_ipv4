#!/usr/bin/env python3
"""Discover tracked Moodle plugins and build one ZIP per component plus manifest."""
from pathlib import Path
import argparse
import json
import re
import subprocess
import zipfile


def build(root: Path, output: Path) -> dict:
    tracked = subprocess.check_output(['git', '-C', str(root), 'ls-files', '-z']).decode().split('\0')
    files = [Path(name) for name in tracked if name]
    excluded = {'.git', '.github', 'work', 'vendor', 'dist', '__pycache__'}
    plugins = []
    for path in files:
        if path.name != 'version.php' or excluded.intersection(path.parts):
            continue
        text = (root / path).read_text()
        match = re.search(r"\$plugin->component\s*=\s*(['\"])([a-z][a-z0-9]*_[a-z][a-z0-9_]*)\1\s*;", text)
        if not match:
            raise ValueError(f'Missing literal $plugin->component in {path}')
        component = match[2]
        kind, name = component.split('_', 1)
        plugins.append({'component': component, 'type': kind, 'name': name,
                        'source': path.parent.as_posix(), 'archive': component + '.zip'})
    if not plugins:
        raise ValueError('No tracked Moodle plugins found')
    if len({p['component'] for p in plugins}) != len(plugins):
        raise ValueError('Duplicate plugin components')
    output.mkdir(parents=True, exist_ok=True)
    roots = {Path(p['source']) for p in plugins}
    for plugin in plugins:
        source = Path(plugin['source'])
        with zipfile.ZipFile(output / plugin['archive'], 'w', zipfile.ZIP_DEFLATED) as archive:
            for path in sorted(files):
                if excluded.intersection(path.parts) or not path.is_relative_to(source):
                    continue
                relative = path.relative_to(source)
                if source == Path('.'):
                    # This legacy repository has a plugin at root and development
                    # tooling alongside it. Other plugins are separate packages.
                    if relative.parts[0] in {'tools', 'docs', 'deploy.sh', 'AGENTS.md', '.gitignore'}:
                        continue
                    if relative.parts[0].startswith('.'):
                        continue
                    containers = {p.parts[0] for p in roots if p != Path('.')}
                    if relative.parts[0] in containers:
                        continue
                if any(other != source and other.is_relative_to(source) and path.is_relative_to(other)
                       for other in roots):
                    continue
                real = root / path
                if real.is_symlink() or not real.is_file():
                    raise ValueError(f'Expected regular tracked file: {path}')
                archive.write(real, plugin['name'] + '/' + relative.as_posix())
        print(output / plugin['archive'])
    manifest = {'plugins': sorted(plugins, key=lambda p: p['component'])}
    (output / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    return manifest


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=Path(__file__).resolve().parents[1] / 'dist')
    args = parser.parse_args()
    build(Path(__file__).resolve().parents[1], args.output)
