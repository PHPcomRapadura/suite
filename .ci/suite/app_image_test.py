"""Application publication is driven only by fake GitHub, registry and Docker."""
import copy
import io
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch
sys.dont_write_bytecode=True
import app_ci as ci
import app_ci_test as fixture
import app_image as image

TOKEN='ghp_'+'w'*36
GH_TOKEN='synthetic-actions-reader'
DIGEST=ci.IMAGE+'@sha256:'+'a'*64


def environment():
    return {'GITHUB_ACTIONS':'true','RUNNER_ENVIRONMENT':'github-hosted','GITHUB_REF':'refs/heads/main',
        'GITHUB_REPOSITORY':ci.SOURCE,'GITHUB_REPOSITORY_ID':str(ci.SOURCE_ID),'GITHUB_EVENT_NAME':'push',
        'GITHUB_SHA':'f'*40,'GITHUB_RUN_ID':'50','GITHUB_RUN_ATTEMPT':'2','GH_TOKEN':GH_TOKEN,
        'GITHUB_TOKEN':'must-not-reach-docker','SUITE_IMAGE_TOKEN':TOKEN}


class Publication(unittest.TestCase):
    def setUp(self):
        tmp=tempfile.TemporaryDirectory();self.addCleanup(tmp.cleanup);self.root=Path(tmp.name)
        self.actual_publisher=image.publisher_identity;self.actual_private=image.private_package
        self.request=fixture.request();self.env=environment();self.calls=[];self.labels={};self.mode='ok';self.pushed=False
        self.rows=fixture.source_api();self.registry=None
        archive=io.BytesIO()
        import zipfile
        with zipfile.ZipFile(archive,'w')as z:z.writestr('suite-'+fixture.REV+'.tar.zst',b'zstd-fixture')
        self.zip=archive.getvalue();self.rows['actions/artifacts/30']['digest']='sha256:'+ci.sha(self.zip)
        for obj,name,options in [(image,'publisher_identity',{}),(image,'private_package',{}),
                (image.runtime,'package',{'return_value':{'visibility':'private'}}),
                (ci,'decompress',{'return_value':fixture.archive()}),
                (image.runtime,'command',{'side_effect':self.command})]:
            p=patch.object(obj,name,**options);p.start();self.addCleanup(p.stop)

    def api(self,path,raw=False):
        if raw:self.assertEqual(path,'actions/artifacts/30/zip');return self.zip
        return copy.deepcopy(self.rows[path])

    def command(self,argv,payload=None,**kwargs):
        self.calls.append((argv,payload,kwargs));env=kwargs['env'];self.registry=Path(env['DOCKER_CONFIG'])
        self.assertTrue(self.registry.is_dir())
        self.assertFalse(any(k.startswith('SUITE_')or k in ['GH_TOKEN','GITHUB_TOKEN']for k in env))
        for token in [TOKEN,GH_TOKEN,self.env['GITHUB_TOKEN']]:self.assertNotIn(token,' '.join(argv))
        if argv[:2]==['docker','login']:self.assertEqual(payload,TOKEN.encode())
        if argv[:2]==['docker','build']:
            self.assertIn('--network=none',argv);self.assertIn('--platform=linux/amd64',argv)
            self.labels={argv[n+1].split('=',1)[0]:argv[n+1].split('=',1)[1]for n,v in enumerate(argv)if v=='--label'}
            if self.mode=='source-drift':self.rows['actions/runs/20/attempts/1/jobs?per_page=100']['jobs'][0]['conclusion']='failure'
        if argv[:2]==['docker','run']:
            for flag in ['--network=none','--read-only','--cap-drop=ALL','--user=999:999']:self.assertIn(flag,argv)
            parent=argv[-1]==image.runtime.VERIFY
            return (b'bad'if self.mode=='runtime'or self.mode=='code'and not parent else
                b'suite-runtime-verified'if parent else b'suite-application-verified')
        if argv[:2]==['docker','push']:self.pushed=True
        if argv[:3]==['docker','image','inspect']:
            parent=argv[-1]==self.request['parent']['image']
            labels={'org.opencontainers.image.source':image.runtime.SOURCE,'org.opencontainers.image.revision':'a'*40,
                'rapadura.net/suite-context-sha256':ci.sha(json.dumps(self.request['parent']['context_files'],sort_keys=True).encode())}if parent else self.labels.copy()
            info={'Id':self.request['parent']['image_id']if parent else 'sha256:'+'9'*64,
                'RepoDigests':[self.request['parent']['image']]if parent else [DIGEST],
                'Config':{'User':'999:999','Labels':labels,'Env':[]}}
            if self.mode=='root'and not parent:info['Config']['User']='0'
            if self.mode=='secret'and not parent:info['Config']['Env']=['APP_KEY=PRIVATE_FIXTURE']
            if self.mode=='parent-id'and parent:info['Id']='sha256:'+'8'*64
            if self.mode=='label'and not parent:labels['org.opencontainers.image.revision']='0'*40
            if self.mode=='digest'and not parent:info['RepoDigests']=['ghcr.io/foreign/runtime@sha256:'+'a'*64]
            if self.mode=='tag-change'and self.pushed:info['Id']='sha256:'+'8'*64
            return json.dumps([info]).encode()
        return b''

    def produce(self):return image.produce(self.request,self.root/'out',self.env,self.api)

    def test_publication_binds_exact_artifact_parent_code_and_ci_without_deploying(self):
        value=self.produce()
        self.assertEqual(value['image'],DIGEST);self.assertEqual(value['image_ci_run'],50);self.assertEqual(value['image_ci_attempt'],2)
        self.assertEqual(value['image_ci_commit'],'f'*40);self.assertEqual(value['request'],self.request)
        self.assertFalse(value['deployed']);self.assertFalse(value['production_state_used']);self.assertFalse(value['repository_scripts_executed'])
        self.assertEqual(value['context']['code_files'],5);self.assertTrue(self.pushed);self.assertFalse(self.registry.exists())
        path=self.root/'out/image-release.json';self.assertEqual(json.loads(path.read_text()),value);self.assertEqual(path.stat().st_mode&0o777,0o600)
        self.assertEqual(self.env['SUITE_IMAGE_TOKEN'],TOKEN)
        docker=(self.root/'out/context/Dockerfile').read_text();self.assertNotIn('artisan',docker);self.assertNotIn('composer',docker)
        self.assertNotIn(TOKEN,path.read_text());self.assertNotIn(GH_TOKEN,path.read_text())

    def checked_php(self):
        rows=[json.loads(payload)for argv,payload,_ in self.calls if argv[:2]==['docker','run']and argv[-1]==image.VERIFY]
        self.assertEqual(len(rows),1);return rows[0]['php']

    def test_the_application_check_wants_the_php_of_the_parents_recipe(self):
        self.produce();self.assertEqual(self.checked_php(),'8.4.28')
        self.request['parent']['context_files']['Dockerfile']=fixture.RECIPE_8_4_27;self.calls=[]
        with tempfile.TemporaryDirectory()as d:image.produce(self.request,Path(d)/'out',self.env,self.api)
        self.assertEqual(self.checked_php(),'8.4.27')

    def test_a_parent_without_a_qualified_php_is_refused_before_docker(self):
        self.request['parent']['context_files']['Dockerfile']='e'*64
        with self.assertRaisesRegex(ValueError,'no qualified PHP'):self.produce()
        self.assertFalse(self.calls)

    def test_the_verifier_compares_the_running_php_with_the_one_it_is_given(self):
        # The real php: a hardcoded version in the verifier fails this.
        if subprocess.run(['sh','-c','command -v php'],capture_output=True).returncode:self.skipTest('no php binary')
        actual=subprocess.run(['php','-r','echo PHP_VERSION;'],capture_output=True,check=True).stdout.decode()
        def verify(payload):
            return subprocess.run(['php','-n','-r',image.VERIFY],input=json.dumps(payload).encode(),capture_output=True).returncode
        for other in ['8.4.27','8.4.28']:
            if other!=actual:self.assertEqual(verify({'php':other,'runtime':{}}),65)
        self.assertEqual(verify({'runtime':{}}),65)
        # Past the version, the next refusal is a missing extension (-n loads none).
        self.assertEqual(verify({'php':actual,'runtime':{}}),66)

    def test_local_pr_fork_and_unprotected_branch_refuse_before_any_api_or_registry(self):
        for key,value in [('GITHUB_ACTIONS','false'),('RUNNER_ENVIRONMENT','self-hosted'),('GITHUB_REF','refs/pull/1/merge'),
                ('GITHUB_REPOSITORY','foreign/suite'),('GITHUB_REPOSITORY_ID','7'),('GITHUB_EVENT_NAME','pull_request'),
                ('SUITE_IMAGE_TOKEN',''),('GH_TOKEN','')]:
            env=self.env.copy();env[key]=value
            with self.subTest(key=key),patch.object(image,'publisher_identity')as registry,patch.object(ci,'source')as source,self.assertRaises(ValueError):
                image.produce(self.request,self.root/'out',env,self.api)
            registry.assert_not_called();source.assert_not_called()
        self.assertFalse(self.calls)

    def test_unqualified_runtime_code_user_config_labels_and_source_never_push(self):
        for mode in ['runtime','code','root','secret','label','parent-id','source-drift']:
            with self.subTest(mode=mode):
                self.mode=mode;self.calls=[];self.rows=fixture.source_api();self.rows['actions/artifacts/30']['digest']='sha256:'+ci.sha(self.zip)
                with tempfile.TemporaryDirectory()as d,self.assertRaises(ValueError):
                    image.produce(self.request,Path(d)/'out',self.env,self.api)
                self.assertFalse(any(argv[:2]==['docker','push']for argv,_,_ in self.calls))

    def test_download_digest_mismatch_refuses_before_package_credentials_or_docker(self):
        self.rows['actions/artifacts/30']['digest']='sha256:'+'0'*64
        with patch.object(image,'publisher_identity')as publisher,self.assertRaises(ValueError):self.produce()
        publisher.assert_not_called();self.assertFalse(self.calls)

    def test_private_package_failure_and_source_failure_never_build(self):
        with patch.object(image,'private_package',side_effect=ValueError('not private')),self.assertRaises(ValueError):self.produce()
        self.assertFalse(self.calls)
        self.rows['actions/runs/20/attempts/1/jobs?per_page=100']['jobs'][0]['conclusion']='failure'
        with patch.object(image,'publisher_identity')as publisher,self.assertRaises(ValueError):self.produce()
        publisher.assert_not_called();self.assertFalse(self.calls)

    def test_foreign_digest_and_changed_pushed_tag_never_write_success_receipt(self):
        for mode in ['digest','tag-change']:
            self.mode=mode
            with self.subTest(mode=mode),tempfile.TemporaryDirectory()as d,self.assertRaises(ValueError):
                dest=Path(d)/'out';image.produce(self.request,dest,self.env,self.api)
            self.assertFalse((dest/'image-release.json').exists())

    def test_package_writer_is_owner_and_has_only_package_scopes(self):
        for value,scopes in [({'login':'other'},{'read:packages','write:packages'}),
                ({'login':'rtio'},{'repo','write:packages'}),({'login':'rtio'},{'read:packages'}),
                ({'login':'rtio'},{'write:packages','delete:packages'})]:
            with patch.object(image,'package_api',return_value=(value,scopes)),self.assertRaises(ValueError):self.actual_publisher(TOKEN)
        with patch.object(image,'package_api',return_value=({'login':'rtio'},{'read:packages','write:packages'})):self.actual_publisher(TOKEN)
        with patch.object(image,'package_api',return_value=({'name':'community-suite','visibility':'public'},set())),self.assertRaises(ValueError):self.actual_private(TOKEN)

    def test_source_api_keeps_package_writer_out_of_process_and_bounds_reads(self):
        calls=[]
        def run(argv,**kwargs):
            calls.append((argv,kwargs['env']))
            os.write(kwargs['stdout'].fileno(),b'{}')
            return subprocess.CompletedProcess(argv,0)
        with patch.object(image.subprocess,'run',side_effect=run):
            self.assertEqual(image.API(self.env)(''),{})
        argv,env=calls[0];self.assertEqual(env['GH_TOKEN'],GH_TOKEN);self.assertNotIn('SUITE_IMAGE_TOKEN',env)
        self.assertNotIn('GITHUB_TOKEN',env);self.assertNotIn(TOKEN,' '.join(argv))
        def oversized(argv,**kwargs):
            os.write(kwargs['stdout'].fileno(),b'x'*(1024**2+3));return subprocess.CompletedProcess(argv,0)
        with patch.object(ci,'MAX_COMPRESSED',2),patch.object(image.subprocess,'run',side_effect=oversized),self.assertRaises(ValueError):
            image.API(self.env)('actions/artifacts/30/zip',raw=True)

    def test_request_environment_uses_literal_numbers_and_parent_json(self):
        values={'SUITE_REVISION':fixture.REV,'SUITE_SOURCE_RUN':'20','SUITE_SOURCE_ATTEMPT':'1','SUITE_ARTIFACT_ID':'30',
            'SUITE_ARTIFACT_SHA256':self.request['artifact_sha256'],'SUITE_PARENT_METADATA':json.dumps(self.request['parent'])}
        self.assertEqual(image.from_environment(values),self.request)
        values['SUITE_ARTIFACT_ID']='30; echo unsafe'
        with self.assertRaises(ValueError):image.from_environment(values)


if __name__=='__main__':unittest.main()
