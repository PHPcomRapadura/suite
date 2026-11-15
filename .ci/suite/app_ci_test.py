"""Suite source, archive and empty-volume boundaries; no live services."""
import copy
import io
import json
import os
from pathlib import Path
from unittest.mock import patch
import subprocess
import sys
import tarfile
import tempfile
import unittest
import zipfile
sys.dont_write_bytecode=True
import app_ci as c

REV='b'*40


def request():
    return {'format':'suite-application-ci-request/1','revision':REV,'source_run':20,'source_attempt':1,
        'artifact_id':30,'artifact_sha256':c.sha(b'zstd-fixture'),'parent':{
            'format':'suite-runtime-ci-publication/1','commit':'a'*40,'run':10,'attempt':1,
            'image':c.PARENT+'@sha256:'+'c'*64,'image_id':'sha256:'+'d'*64,
            'context_files':{name:'e'*64 for name in c.PARENT_FILES},'runtime_verified':True,
            'private_package_verified':True,'application_code_included':False,'configuration_included':False,
            'deployed':False,'profile':'measured-2-64'}}


def archive(extra=None,executable=()):
    files={'artisan':b'<?php','composer.lock':b'{}','public/index.php':b'<?php',
        'REVISION':(REV+'\n').encode(),'app/Some Code.php':b'<?php class SomeCode {}',
        'README.md':b'not runtime','bootstrap/cache/config.php':b'fixture-build-cache'}
    files.update(extra or {})
    stream=io.BytesIO()
    with tarfile.open(fileobj=stream,mode='w')as tar:
        for name,payload in files.items():
            info=tarfile.TarInfo('./'+name);info.size=len(payload);info.mode=0o755 if name in executable else 0o644;tar.addfile(info,io.BytesIO(payload))
    return stream.getvalue()


def source_api():
    target={'id':c.SOURCE_ID}
    jobs=[{'name':name,'head_sha':REV,'run_id':20,'status':'completed','conclusion':'success',
        'started_at':'2026-11-15T12:00:00Z','completed_at':'2026-11-15T12:05:00Z'}for name in ['Quality','Build release']]
    return {'':{'id':c.SOURCE_ID,'full_name':c.SOURCE,'private':False,'default_branch':'main'},
        'actions/workflows/ci.yaml':{'id':40,'name':'CI/CD','path':'.github/workflows/ci.yaml','state':'active'},
        'actions/runs/20':{'id':20,'head_sha':REV,'head_branch':'main','workflow_id':40,
            'path':'.github/workflows/ci.yaml','event':'push','run_attempt':1,'repository':target,'head_repository':target},
        'compare/'+REV+'...main':{'status':'identical','merge_base_commit':{'sha':REV}},
        'actions/runs/20/attempts/1/jobs?per_page=100':{'total_count':2,'jobs':jobs},
        'actions/artifacts/30':{'id':30,'expired':False,'name':'suite-'+REV+'-1','size_in_bytes':2048,
            'digest':'sha256:'+'a'*64,'created_at':'2026-11-15T12:04:00Z','workflow_run':{'id':20,'repository_id':c.SOURCE_ID,
                'head_repository_id':c.SOURCE_ID,'head_sha':REV,'head_branch':'main'}}}


class CI(unittest.TestCase):
    def test_request_and_parent_are_exact_typed_and_do_not_alias_caller_state(self):
        value=request();checked=c.checked(value);checked['parent']['context_files']['rapadura']='f'*64
        self.assertEqual(value['parent']['context_files']['rapadura'],'e'*64)
        for path,replacement in [('source_run',True),('artifact_sha256','0'*64),('revision','main')]:
            bad=request();bad[path]=replacement
            with self.assertRaises(ValueError):c.checked(bad)
        for key,value in [('deployed',True),('runtime_verified',1),('application_code_included',True),
                ('profile','source-8-512'),('image','ghcr.io/foreign/runtime@sha256:'+'c'*64)]:
            bad=request();bad['parent'][key]=value
            with self.assertRaises(ValueError):c.checked(bad)
        with self.assertRaises(ValueError):c.decoded(b'{"x":1,"x":2}')

    def test_exact_source_checks_and_attempt_bound_artifact(self):
        rows=source_api()
        self.assertEqual(c.source(request(),rows.__getitem__),{'repository':c.SOURCE,'run':20,'attempt':1,'artifact':30,'artifact_zip_sha256':'a'*64})
        cases=[('', 'id',7),('', 'private',True),('actions/runs/20','head_branch','feature'),
            ('actions/runs/20','event','pull_request'),('actions/artifacts/30','expired',True),
            ('actions/artifacts/30','name','suite-'+REV+'-2'),
            ('actions/artifacts/30','created_at','2026-11-15T11:00:00Z')]
        for path,key,value in cases:
            bad=copy.deepcopy(rows);bad[path][key]=value
            with self.subTest(path=path,key=key),self.assertRaises(ValueError):c.source(request(),bad.__getitem__)
        for name in ['Quality','Build release']:
            bad=copy.deepcopy(rows)
            next(j for j in bad['actions/runs/20/attempts/1/jobs?per_page=100']['jobs']if j['name']==name)['conclusion']='failure'
            with self.assertRaises(ValueError):c.source(request(),bad.__getitem__)

    def test_newer_failed_checks_and_incomplete_api_pages_refuse(self):
        rows=source_api();rows['actions/runs/20']['run_attempt']=2
        newer=copy.deepcopy(rows['actions/runs/20/attempts/1/jobs?per_page=100'])
        newer['jobs'][0]['conclusion']='failure';rows['actions/runs/20/attempts/2/jobs?per_page=100']=newer
        with self.assertRaises(ValueError):c.source(request(),rows.__getitem__)
        rows=source_api();rows['actions/runs/20/attempts/1/jobs?per_page=100']['total_count']=101
        with self.assertRaises(ValueError):c.source(request(),rows.__getitem__)
        rows=source_api();rows['compare/'+REV+'...main']['merge_base_commit']['sha']='a'*40
        with self.assertRaises(ValueError):c.source(request(),rows.__getitem__)

    def test_decompression_bounds_subprocess_and_rejects_errors_without_a_context(self):
        calls=[]
        def run(argv,**kwargs):
            calls.append((argv,kwargs))
            os.write(kwargs['stdout'].fileno(),archive())
            return subprocess.CompletedProcess(argv,0)
        with patch.object(c.subprocess,'run',side_effect=run):
            self.assertEqual(c.decompress(b'compressed-fixture'),archive())
        argv,options=calls[0]
        self.assertEqual(argv,['zstd','--decompress','--stdout','--threads=1'])
        self.assertEqual(options['input'],b'compressed-fixture');self.assertEqual(options['timeout'],120)
        with patch.object(c.resource,'setrlimit')as limit:
            options['preexec_fn']();limit.assert_called_once_with(c.resource.RLIMIT_FSIZE,(c.MAX_TAR,c.MAX_TAR))
        with patch.object(c.subprocess,'run',return_value=subprocess.CompletedProcess(['zstd'],1)),self.assertRaises(ValueError):
            c.decompress(b'bad')
        with patch.object(c,'MAX_TAR',3),patch.object(c.subprocess,'run',side_effect=run),self.assertRaises(ValueError):
            c.decompress(b'too-large')
        with patch.object(c.subprocess,'run')as command,self.assertRaises(ValueError):c.decompress(b'')
        command.assert_not_called()

    def test_partial_publisher_rerun_can_use_original_successful_build_artifact(self):
        rows=source_api();rows['actions/runs/20']['run_attempt']=2
        rows['actions/runs/20/attempts/2/jobs?per_page=100']={'total_count':1,'jobs':[{'name':'Build CI image / publish','status':'in_progress'}]}
        self.assertEqual(c.source(request(),rows.__getitem__)['attempt'],1)
        rows['actions/artifacts/30']['name']='suite-'+REV+'-2'
        with self.assertRaises(ValueError):c.source(request(),rows.__getitem__)

    def test_zip_envelope_is_one_literal_checksum_bound_release(self):
        def zipfile_bytes(files):
            stream=io.BytesIO()
            with zipfile.ZipFile(stream,'w')as z:
                for name,payload in files:z.writestr(name,payload)
            return stream.getvalue()
        name='suite-'+REV+'.tar.zst'
        self.assertEqual(c.envelope(request(),zipfile_bytes([(name,b'zstd-fixture')])),b'zstd-fixture')
        for files in [[('../'+name,b'zstd-fixture')],[(name,b'changed')],[(name,b'zstd-fixture'),('extra',b'')]]:
            with self.assertRaises(ValueError):c.envelope(request(),zipfile_bytes(files))

    def test_context_contains_sanitized_code_and_fixed_network_free_recipe(self):
        with tempfile.TemporaryDirectory()as d:
            root=Path(d);dest=root/'context';receipt=c.prepare(request(),archive(),dest)
            self.assertEqual(receipt['code_files'],5)
            self.assertNotIn('RUN php',(dest/'Dockerfile').read_text())
            self.assertIn('FROM '+request()['parent']['image'],(dest/'Dockerfile').read_text())
            self.assertNotIn('README.md',{p.relative_to(dest/'seed').as_posix()for p in (dest/'seed').rglob('*')})
            self.assertFalse((dest/'seed/bootstrap/cache/config.php').exists())
            self.assertFalse((dest/'seed/.env').exists())
            manifest=(dest/'code-manifest.json').read_bytes();self.assertEqual(c.sha(manifest),receipt['code_manifest_sha256'])
            self.assertEqual((dest/'seed/app/Some Code.php').stat().st_mode&0o777,0o644)
            with self.assertRaises(ValueError):c.prepare(request(),archive(),dest)

    def test_source_executable_flags_survive_the_sanitized_image_context(self):
        with tempfile.TemporaryDirectory()as d:
            dest=Path(d)/'context';c.prepare(request(),archive(executable={'artisan'}),dest)
            self.assertEqual((dest/'seed/artisan').stat().st_mode&0o777,0o755)
            manifest=json.loads((dest/'code-manifest.json').read_text())
            self.assertEqual(manifest['modes']['artisan'],0o755)

    def test_archive_refuses_state_revision_paths_links_duplicates_and_special_modes(self):
        for extra in [{'REVISION':b'other\n'},{'app/key.env':b'SQLite format 3\0'},
                {'database/database.sqlite':b'database'},{'config/.env':b'PRIVATE_FIXTURE'},
                {'.env':b'PRIVATE_FIXTURE'},{'../escape':b'x'},{'app\\escape.php':b'x'},{'app/bad\nname.php':b'x'}]:
            with self.subTest(extra=list(extra)),self.assertRaises(ValueError):c.unpack(archive(extra),REV)
        for kind in ['symlink','hardlink','fifo','setuid','duplicate']:
            stream=io.BytesIO()
            with tarfile.open(fileobj=stream,mode='w')as tar:
                info=tarfile.TarInfo('app/bad');info.mode=0o644
                if kind=='symlink':info.type=tarfile.SYMTYPE;info.linkname='/outside'
                elif kind=='hardlink':info.type=tarfile.LNKTYPE;info.linkname='/outside'
                elif kind=='fifo':info.type=tarfile.FIFOTYPE
                elif kind=='setuid':info.mode=0o4755
                tar.addfile(info)
                if kind=='duplicate':tar.addfile(info)
            with self.subTest(kind=kind),self.assertRaises(ValueError):c.unpack(stream.getvalue(),REV)

    def test_seed_copies_only_empty_target_and_refuses_stale_foreign_or_changed_current(self):
        with tempfile.TemporaryDirectory()as d:
            root=Path(d);context=root/'context';c.prepare(request(),archive(),context)
            base=root/'suite';(base/'shared').mkdir(parents=True);(base/'shared/.env').write_text('PRIVATE_FIXTURE')
            script=c.seed(REV).replace('base=/suite','base='+str(base)).replace('/usr/local/share/suite-code.sha256',str(context/'code.sha256')).replace('cp -R /seed ', 'cp -R '+str(context/'seed')+' ')
            runner=root/'seed.sh';runner.write_text(script)
            def run():return subprocess.run(['/bin/sh',str(runner)],capture_output=True,timeout=10).returncode
            self.assertEqual(run(),0);self.assertEqual(os.readlink(base/'current'),'releases/'+REV)
            self.assertEqual((base/'current/REVISION').read_text(),REV+'\n')
            self.assertTrue((base/'current/.env').is_symlink());self.assertTrue((base/'current/storage').is_symlink())
            self.assertEqual(run(),0)
            public=base/'current/public/index.php';payload=public.read_bytes();public.unlink()
            outside=root/'same-bytes.php';outside.write_bytes(payload);public.symlink_to(outside)
            self.assertNotEqual(run(),0,'matching hash must not admit a foreign code symlink')
            public.unlink();public.write_bytes(payload)
            (base/'current/artisan').write_text('changed');self.assertNotEqual(run(),0)
            (base/'current').unlink();(base/'current').symlink_to('/outside');self.assertNotEqual(run(),0)
            (base/'current').unlink();(base/'current').symlink_to('releases/'+'a'*40);self.assertNotEqual(run(),0)

    def test_seed_refuses_partial_target_and_missing_environment_before_copy(self):
        with tempfile.TemporaryDirectory()as d:
            root=Path(d);base=root/'suite';base.mkdir();runner=root/'seed.sh'
            runner.write_text(c.seed(REV).replace('base=/suite','base='+str(base)))
            self.assertEqual(subprocess.run(['/bin/sh',str(runner)],capture_output=True).returncode,65)
            self.assertFalse((base/'releases').exists())
            (base/'shared').mkdir();(base/'shared/.env').write_text('PRIVATE_FIXTURE')
            (base/'releases'/REV).mkdir(parents=True)
            self.assertEqual(subprocess.run(['/bin/sh',str(runner)],capture_output=True).returncode,65)
            self.assertFalse((base/'current').exists())


if __name__=='__main__':unittest.main()
