"""Packaging contract checks using temporary Git repositories."""
import importlib.util
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
import zipfile

spec = importlib.util.spec_from_file_location('package', Path(__file__).resolve().parents[1] / 'package.py')
package = importlib.util.module_from_spec(spec)
spec.loader.exec_module(package)


class PackagingTest(unittest.TestCase):
    def test_discovery_and_package_boundaries(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            for name, component in [('.', 'qtype_ipv4'), ('mod/practicum', 'mod_practicum'),
                                    ('local/example', 'local_example')]:
                path = root / name
                path.mkdir(parents=True, exist_ok=True)
                (path / 'version.php').write_text(f"<?php $plugin->component = '{component}';")
            (root / 'question.php').write_text('<?php')
            (root / 'deploy.sh').write_text('deployment helper')
            (root / 'docs').mkdir()
            (root / 'docs/notes.md').write_text('developer docs')
            (root / 'secret.txt').write_text('untracked secret')
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            subprocess.run(['git', '-C', str(root), 'add', 'version.php', 'question.php',
                            'mod', 'local', 'deploy.sh', 'docs'], check=True)
            manifest = package.build(root, root / 'dist')
            self.assertEqual({p['component'] for p in manifest['plugins']},
                             {'qtype_ipv4', 'mod_practicum', 'local_example'})
            with zipfile.ZipFile(root / 'dist/qtype_ipv4.zip') as z:
                self.assertEqual(set(z.namelist()), {'ipv4/version.php', 'ipv4/question.php'})
            with zipfile.ZipFile(root / 'dist/local_example.zip') as z:
                self.assertEqual(z.namelist(), ['example/version.php'])
            self.assertEqual(manifest, json.loads((root / 'dist/manifest.json').read_text()))

    def test_duplicate_component_rejected(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            for folder in ['local/one', 'local/two']:
                path = root / folder
                path.mkdir(parents=True)
                (path / 'version.php').write_text("<?php $plugin->component = 'local_same';")
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            subprocess.run(['git', '-C', str(root), 'add', '.'], check=True)
            with self.assertRaisesRegex(ValueError, 'Duplicate'):
                package.build(root, root / 'dist')


if __name__ == '__main__':
    unittest.main()
