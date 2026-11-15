"""Publish exact Suite release images in main's package-only CI environment."""
import argparse
import base64
import http.client
import json
import os
from pathlib import Path
import re
import resource
import subprocess
import sys
import tempfile
sys.dont_write_bytecode=True
import app_ci as ci
import runtime_publish as runtime

VERIFY=r'''
$e=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
if(PHP_VERSION!=="8.4.27")exit(65);
foreach(["pdo_mysql","mbstring","bcmath","gd","intl","zip","redis","Zend OPcache"]as$x)if(!extension_loaded($x))exit(66);
foreach(["suite-operations","rapadura","deployd"]as$n)if(hash_file("sha256","/usr/local/bin/".$n)!==$e["runtime"][$n])exit(67);
foreach(["php-fpm.conf"=>"/usr/local/etc/php-fpm.d/app.conf","nginx.conf"=>"/etc/nginx/nginx.conf","artisan-apps.conf"=>"/etc/rapadura/artisan-apps.conf","suite-deploy"=>"/usr/local/bin/suite-deploy","worker.sh"=>"/usr/local/bin/suite-worker","service.sh"=>"/usr/local/bin/suite-service"]as$n=>$path)if(hash_file("sha256",$path)!==$e["runtime"][$n])exit(68);
foreach(["manifest"=>"/usr/local/share/suite-code-manifest.json","sums"=>"/usr/local/share/suite-code.sha256","seed"=>"/usr/local/bin/suite-seed"]as$n=>$path)if(hash_file("sha256",$path)!==$e[$n])exit(69);
$m=json_decode(file_get_contents("/usr/local/share/suite-code-manifest.json"),true,512,JSON_THROW_ON_ERROR);
$a=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("/seed",FilesystemIterator::SKIP_DOTS));
foreach($it as$f){if($f->isLink())exit(70);if($f->isDir())continue;if(!$f->isFile())exit(70);$n=substr($f->getPathname(),6);if(($f->getPerms()&0777)!==$m["modes"][$n])exit(70);$a[$n]=hash_file("sha256",$f->getPathname());}
ksort($a);ksort($m["files"]);if($a!==$m["files"]||file_exists("/suite/.env"))exit(71);
echo "suite-application-verified";
'''


def environment(request,values):
    if (any(values.get(k)!=v for k,v in {'GITHUB_ACTIONS':'true','RUNNER_ENVIRONMENT':'github-hosted',
            'GITHUB_REF':'refs/heads/main','GITHUB_REPOSITORY':ci.SOURCE,'GITHUB_REPOSITORY_ID':str(ci.SOURCE_ID)}.items())
            or values.get('GITHUB_EVENT_NAME')not in ['push','workflow_dispatch']
            or not re.fullmatch('[a-f0-9]{40}',values.get('GITHUB_SHA',''))
            or any(not re.fullmatch('[1-9][0-9]{0,17}',values.get(k,''))for k in ['GITHUB_RUN_ID','GITHUB_RUN_ATTEMPT'])
            or not 1<=len(values.get('GH_TOKEN',''))<=4096
            or not re.fullmatch('ghp_[A-Za-z0-9]{36}',values.get('SUITE_IMAGE_TOKEN',''))):
        raise ValueError('main-only hosted source CI and package-only publisher required')
    return int(values['GITHUB_RUN_ID']),int(values['GITHUB_RUN_ATTEMPT'])


def from_environment(values):
    result={'format':'suite-application-ci-request/1'}
    for field,suffix in [('revision','REVISION'),('source_run','SOURCE_RUN'),('source_attempt','SOURCE_ATTEMPT'),
            ('artifact_id','ARTIFACT_ID'),('artifact_sha256','ARTIFACT_SHA256'),('parent','PARENT_METADATA')]:
        value=values.get('SUITE_'+suffix,'')
        if not isinstance(value,str)or len(value.encode())>16384:raise ValueError('bounded source CI request required')
        if field in ['source_run','source_attempt','artifact_id']:
            if not re.fullmatch('[1-9][0-9]{0,17}',value):raise ValueError('literal source execution required')
            value=int(value)
        elif field=='parent':value=ci.decoded(value.encode(),16384)
        result[field]=value
    return ci.checked(result)


class API:
    def __init__(self,values):
        self.env={k:v for k,v in values.items()if k not in ['SUITE_IMAGE_TOKEN','GITHUB_TOKEN']}
    def __call__(self,path,raw=False):
        budget=ci.MAX_COMPRESSED+1024**2 if raw else 8*1024**2
        with tempfile.TemporaryFile()as output,tempfile.TemporaryFile()as error:
            def limit():resource.setrlimit(resource.RLIMIT_FSIZE,(budget,budget))
            result=subprocess.run(['gh','api','repos/'+ci.SOURCE+('/'+path if path else '')],
                env=self.env,stdout=output,stderr=error,timeout=300,preexec_fn=limit)
            if result.returncode or os.fstat(output.fileno()).st_size>budget:raise ValueError('bounded source API read refused')
            output.seek(0);blob=output.read()
        return blob if raw else ci.decoded(blob,budget)


def package_api(token,path):
    connection=http.client.HTTPSConnection('api.github.com',timeout=30)
    try:
        connection.request('GET',path,headers={'Authorization':'Bearer '+token,'Accept':'application/vnd.github+json',
            'X-GitHub-Api-Version':'2022-11-28','User-Agent':'rapadura-suite-application-ci/1'})
        response=connection.getresponse();blob=response.read(1024**2+1)
        if response.status!=200 or len(blob)>1024**2:raise ValueError('package metadata read refused')
        return ci.decoded(blob),{s.strip()for s in response.getheader('X-OAuth-Scopes','').split(',')if s.strip()}
    finally:connection.close()


def publisher_identity(token):
    value,scopes=package_api(token,'/user')
    if (value.get('login')!='rtio'or 'write:packages'not in scopes
            or not scopes<={'read:packages','write:packages'}):
        raise ValueError('owner package-only credential required')


def private_package(token):
    value,_=package_api(token,'/user/packages/container/community-suite')
    if value.get('name')!='community-suite'or value.get('visibility')!='private':raise ValueError('existing private Suite package required')


def inspect(reference,labels,env):
    values=ci.decoded(runtime.command(['docker','image','inspect',reference],env=env))
    if not isinstance(values,list)or len(values)!=1:raise ValueError('one image identity required')
    image=values[0];config=image.get('Config')or{}
    if (config.get('User')!='999:999'or not re.fullmatch('sha256:[a-f0-9]{64}',image.get('Id',''))
            or any(config.get('Labels',{}).get(k)!=v for k,v in labels.items())):
        raise ValueError('nonroot image labels differ')
    for entry in config.get('Env',[]):
        if entry.split('=',1)[0].startswith(('APP_','DB_','MAIL_','SESSION_','CACHE_','QUEUE_','REDIS_',
                'AWS_','CLOUDFLARE_','ADMIN_','DEPLOYD_','GH_','GITHUB_','RESTIC_','KUBE')):
            raise ValueError('runtime configuration in image')
    return image


def smoke(image,program,payload,env):
    return runtime.command(['docker','run','--rm','-i','--network=none','--read-only','--user=999:999',
        '--cap-drop=ALL','--security-opt=no-new-privileges','--memory=256m','--pids-limit=64',
        '--entrypoint=php',image,'-r',program],json.dumps(payload).encode(),env=env,timeout=120)


def produce(request,directory,values=None,api=None):
    request=ci.checked(request);values=dict(os.environ if values is None else values);run,attempt=environment(request,values)
    if not directory.is_absolute()or directory.exists()or directory.is_symlink():raise ValueError('fresh CI output required')
    api=api or API(values);source=ci.source(request,api)
    raw=api('actions/artifacts/'+str(request['artifact_id'])+'/zip',raw=True)
    if ci.sha(raw)!=source['artifact_zip_sha256']:raise ValueError('source artifact download digest differs')
    payload=ci.envelope(request,raw)
    tar=ci.decompress(payload);ci.unpack(tar,request['revision']);ci.source(request,api)
    token=values['SUITE_IMAGE_TOKEN'];publisher_identity(token);private_package(token)
    if runtime.package(token)is None:raise ValueError('qualified private runtime package required')
    directory.mkdir(mode=0o700)
    context=ci.prepare(request,tar,directory/'context');identity=ci.sha(json.dumps(context,sort_keys=True).encode())
    parent=request['parent'];tag=f'{ci.IMAGE}:ci-{run}-{attempt}-{identity[:16]}'
    with tempfile.TemporaryDirectory(prefix='suite-application-registry-')as registry:
        env={k:v for k,v in values.items()if k not in ['GH_TOKEN','GITHUB_TOKEN']and not k.startswith('SUITE_')}
        env['DOCKER_CONFIG']=registry
        runtime.command(['docker','login','ghcr.io','--username','rtio','--password-stdin'],token.encode(),env=env)
        runtime.command(['docker','pull',parent['image']],env=env,timeout=600)
        parent_labels={'org.opencontainers.image.source':runtime.SOURCE,'org.opencontainers.image.revision':parent['commit'],
            'rapadura.net/suite-context-sha256':ci.sha(json.dumps(parent['context_files'],sort_keys=True).encode())}
        original=inspect(parent['image'],parent_labels,env)
        if original['Id']!=parent['image_id']or parent['image']not in original.get('RepoDigests',[]):raise ValueError('immutable runtime identity differs')
        if smoke(original['Id'],runtime.VERIFY,parent['context_files'],env)!=b'suite-runtime-verified':raise ValueError('runtime bytes differ')
        labels={'org.opencontainers.image.source':'https://github.com/'+ci.SOURCE,'org.opencontainers.image.revision':request['revision'],
            'rapadura.net/suite-runtime-image':parent['image'],'rapadura.net/suite-runtime-commit':parent['commit'],
            'rapadura.net/suite-code-manifest-sha256':context['code_manifest_sha256'],'rapadura.net/suite-application-context-sha256':identity}
        command=['docker','build','--platform=linux/amd64','--network=none','--tag',tag]
        for key,value in labels.items():command+=['--label',key+'='+value]
        runtime.command(command+[str(directory/'context')],env=env,timeout=600,output_limit=32*1024**2,
            diagnostic=directory/'build-diagnostic.json',secrets=(token,'rtio:'+token,values['GH_TOKEN']))
        candidate=inspect(tag,labels,env)
        expected={'runtime':parent['context_files'],'manifest':context['code_manifest_sha256'],
            'seed':context['context_files']['seed.sh'],'sums':context['context_files']['code.sha256']}
        if smoke(candidate['Id'],VERIFY,expected,env)!=b'suite-application-verified':raise ValueError('application image bytes differ')
        if inspect(tag,labels,env)['Id']!=candidate['Id']:raise ValueError('built tag changed')
        ci.source(request,api);private_package(token)
        runtime.command(['docker','push',tag],env=env,timeout=600)
        private_package(token);final=inspect(tag,labels,env)
        digests=[v for v in final.get('RepoDigests',[])if re.fullmatch(re.escape(ci.IMAGE)+r'@sha256:[a-f0-9]{64}',v)]
        if final['Id']!=candidate['Id']or len(digests)!=1:raise ValueError('published digest differs')
    ci.source(request,api)
    result={'format':'suite-application-ci-image/1','result':'published privately','request':request,
        'image':digests[0],'image_id':candidate['Id'],'tag':tag,'context':context,'image_ci_run':run,'image_ci_attempt':attempt,
        'image_ci_commit':values['GITHUB_SHA'],'production_state_used':False,'deployed':False,'repository_scripts_executed':False}
    runtime.write_diagnostic(directory/'image-release.json',result)
    return result


def main():
    os.umask(0o077);parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--directory',type=Path,required=True);args=parser.parse_args()
    try:
        result=produce(from_environment(os.environ),args.directory)
        print(json.dumps({k:result[k]for k in ['result','image','image_ci_run','image_ci_attempt']}));return 0
    except Exception:
        print('Suite CI image refused; credentials and diagnostics withheld',file=sys.stderr);return 1


if __name__=='__main__':raise SystemExit(main())
