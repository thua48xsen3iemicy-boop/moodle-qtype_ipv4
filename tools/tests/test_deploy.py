from pathlib import Path
import tempfile, shutil, os, subprocess
repo=Path(__file__).resolve().parents[2]
script=(repo/'deploy.sh').read_text()
for failure, configured in [('', 1000), ('', 9000), ('upgrade.php', 1000),
                            ('purge_caches.php', 1000), ('unknown-type', 1000), ('php-preflight', 1000)]:
 with tempfile.TemporaryDirectory(prefix='deploy-test-', ) as tmp:
  root=Path(tmp); mock=root/'bin';mock.mkdir(); log=root/'commands.log'; php_log=root/'php.log'
  moodle=root/'moodle';(moodle/'public').mkdir(parents=True);(moodle/'public/version.php').write_text('<?php')
  (moodle/'config.php').write_text('<?php')
  for file in ['maintenance.php','upgrade.php','purge_caches.php']:
   path=moodle/'admin/cli'/file;path.parent.mkdir(parents=True,exist_ok=True);path.write_text('<?php')
  for plugin in ['question/type/ipv4','mod/practicum']:
   path=moodle/'public'/plugin;path.mkdir(parents=True);(path/'old.txt').write_text('previous plugin')
  fixture=root/'source';fixture.mkdir()
  for name in ['tools','classes','db','backup','lang','pix','tests','mod']:
   shutil.copytree(repo/name,fixture/name)
  for path in repo.glob('*.php'):shutil.copy(path,fixture/path.name)
  for name in ['LICENSE','README.md','TESTING.md']:shutil.copy(repo/name,fixture/name)
  # A third plugin proves packaging/deployment needs no hardcoded plugin list.
  extra=fixture/'local/example';extra.mkdir(parents=True)
  (extra/'version.php').write_text("<?php $plugin->component = 'local_example';")
  subprocess.run(['/usr/bin/git','init','-q',str(fixture)],check=True)
  subprocess.run(['/usr/bin/git','-C',str(fixture),'add','.'],check=True)
  programs={
   'git':'''#!/usr/bin/env bash
case "$*" in
 *ls-files*) exec /usr/bin/git "$@";;
 *'remote get-url origin'*) echo https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4.git;;
 *'branch --show-current'*) echo main;;
 *'rev-parse'*) echo abc123;;
esac
''',
   'php':'''#!/usr/bin/env python3
import os,sys
from pathlib import Path
args=sys.argv[1:]
import json
with open(os.environ['TEST_PHP_LOG'],'a') as f:f.write(json.dumps(args)+'\\n')
override=None
while args and args[0]=='-d':
 assert args[1].startswith('max_input_vars='),args
 override=int(args[1].split('=',1)[1])
 args=args[2:]
if args[0]=='-l':sys.exit(0)
if args[0]=='-r':
 if 'echo max(5000' in args[1]:
  assert override is None
  print(max(5000,int(os.environ['TEST_CONFIGURED_INPUT_VARS'])))
  sys.exit(0)
 if 'php_ini_loaded_file()' in args[1]:
  if os.environ['FAIL_STAGE']=='php-preflight':sys.exit(19)
  assert override==max(5000,int(os.environ['TEST_CONFIGURED_INPUT_VARS'])),override
  print('PHP CLI: fixture (8.4)\\nLoaded php.ini: /fixture/cli/php.ini\\nDeployment max_input_vars: '+str(override))
  sys.exit(0)
 assert override>=5000,override
 base=Path(os.environ['MOODLE_ROOT'])/'public'
 types={'qtype':str(base/'question/type'),'mod':str(base/'mod')}
 if os.environ['FAIL_STAGE']!='unknown-type': types['local']=str(base/'local')
 print(json.dumps(types))
 sys.exit(0)
with open(os.environ['TEST_LOG'],'a') as f:f.write(Path(args[0]).name+' '+' '.join(args[1:])+'\\n')
if os.environ['FAIL_STAGE']==Path(args[0]).name:sys.exit(17)
''',
   'runuser':'''#!/usr/bin/env bash
shift 3
exec "$@"
''',
   'rsync':'''#!/usr/bin/env python3
import shutil,sys
from pathlib import Path
src,dst=map(Path,sys.argv[-2:])
shutil.rmtree(dst)
shutil.copytree(src,dst)
'''
  }
  for name,code in programs.items():
   path=mock/name;path.write_text(code);path.chmod(0o755)
  candidate=root/'deploy.sh'
  # Test sandbox substitutes only privilege check and fixed runtime temp/lock paths.
  candidate.write_text(script.replace('[[ $EUID -eq 0 ]]','[[ 1 -eq 1 ]]').replace('/var/lock/moodle-plugins-deploy.lock',str(root/'deploy.lock')).replace('/var/tmp/moodle-practicum.XXXXXXXX',str(root/'stage.XXXXXXXX')))
  env=dict(os.environ,PATH=str(mock)+':'+os.environ['PATH'],MOODLE_ROOT=str(moodle),SOURCE_DIR=str(fixture),BACKUP_ROOT=str(root/'backups'),TEST_LOG=str(log),TEST_PHP_LOG=str(php_log),TEST_CONFIGURED_INPUT_VARS=str(configured),FAIL_STAGE=failure,WEB_USER='www-data',PHP_BIN='php')
  result=subprocess.run(['bash',str(candidate)],env=env,capture_output=True,text=True)
  events=log.read_text().splitlines() if log.exists() else []
  import json
  php_calls=[json.loads(line) for line in php_log.read_text().splitlines()]
  for args in php_calls[1:]:
   assert args[:2]==['-d','max_input_vars='+str(max(5000,configured))],args
  if failure=='php-preflight':
   assert result.returncode!=0
   assert not events,events
   assert 'PHP CLI preflight failed before deployment' in result.stderr
   assert (moodle/'public/mod/practicum/old.txt').is_file()
   assert not (root/'backups').exists()
   assert not list(root.glob('stage.*'))
   print('PASS: failed PHP preflight rejected before maintenance or file changes')
   continue
  assert 'Loaded php.ini: /fixture/cli/php.ini' in result.stdout
  assert 'Deployment max_input_vars: '+str(max(5000,configured)) in result.stdout
  if failure=='unknown-type':
   assert result.returncode!=0
   assert not events,events
   assert 'Unknown Moodle plugin type: local' in result.stderr
   assert (moodle/'public/mod/practicum/old.txt').is_file()
   assert not list(root.glob('stage.*'))
   print('PASS: unknown type rejected before maintenance')
   continue
  assert events[0]=='maintenance.php --enable',events
  assert 'upgrade.php --non-interactive' in events,events
  assert (moodle/'public/mod/practicum/version.php').is_file()
  assert (moodle/'public/local/example/version.php').is_file()
  assert list((root/'backups').glob('*/local_example.zip'))
  assert (moodle/'public/question/type/ipv4/classes/exercise.php').is_file()
  assert list((root/'backups').glob('*/mod_practicum-before.tar.gz'))
  assert not list(root.glob('stage.*'))
  if failure:
   assert result.returncode==17,(result.returncode,result.stdout,result.stderr)
   assert 'maintenance.php --disable' not in events,events
   assert 'Maintenance remains ON' in result.stderr
   assert '-d max_input_vars=5000' in result.stderr
   assert 'upgrade.php --non-interactive &&' in result.stderr
   assert 'purge_caches.php &&' in result.stderr
   assert result.stderr.index('upgrade.php --non-interactive') < result.stderr.index('purge_caches.php &&')
  else:
   assert result.returncode==0,(result.stdout,result.stderr)
   assert events==['maintenance.php --enable','upgrade.php --non-interactive','purge_caches.php ','maintenance.php --disable'],events
  print('PASS:',failure or 'successful deployment (configured max_input_vars='+str(configured)+')',events)
