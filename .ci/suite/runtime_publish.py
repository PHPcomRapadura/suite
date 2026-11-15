"""Publish the data-free Suite parent only from protected manual master CI."""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request
sys.dont_write_bytecode=True
import runtime_build as build

REPOSITORY=build.REPOSITORY
SOURCE='https://github.com/rtio/rapadura'
PACKAGE_URL='https://api.github.com/users/rtio/packages/container/community-suite-runtime'
VERIFY=r'''
$e=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
if(PHP_VERSION!=="8.4.27")exit(65);
foreach(["pdo_mysql","mbstring","bcmath","gd","intl","zip","redis","Zend OPcache"]as$x)if(!extension_loaded($x))exit(66);
foreach(["suite-operations","rapadura","deployd"]as$n)if(hash_file("sha256","/usr/local/bin/".$n)!==$e[$n])exit(67);
if(hash_file("sha256","/usr/local/etc/php-fpm.d/app.conf")!==$e["php-fpm.conf"]||hash_file("sha256","/etc/nginx/nginx.conf")!==$e["nginx.conf"])exit(68);
foreach(["suite-deploy"=>"/usr/local/bin/suite-deploy","worker.sh"=>"/usr/local/bin/suite-worker","service.sh"=>"/usr/local/bin/suite-service","artisan-apps.conf"=>"/etc/rapadura/artisan-apps.conf"]as$n=>$path)if(hash_file("sha256",$path)!==$e[$n])exit(68);
if(file_exists("/seed")||file_exists("/suite/.env"))exit(69);
echo "suite-runtime-verified";
'''


def sha(value):return hashlib.sha256(value).hexdigest()


def identity(env):
    expected={'GITHUB_ACTIONS':'true','RUNNER_ENVIRONMENT':'github-hosted','GITHUB_REPOSITORY':'rtio/rapadura',
        'GITHUB_REPOSITORY_ID':'1415963195','GITHUB_REF':'refs/heads/master','GITHUB_EVENT_NAME':'workflow_dispatch'}
    if any(env.get(k)!=v for k,v in expected.items()):raise ValueError('manual protected master CI required')
    if not re.fullmatch('[a-f0-9]{40}',env.get('GITHUB_SHA','')):raise ValueError('exact CI commit required')
    for key in ['GITHUB_RUN_ID','GITHUB_RUN_ATTEMPT']:
        if not re.fullmatch('[1-9][0-9]{0,17}',env.get(key,'')):raise ValueError('exact CI execution required')
    if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9-]*(?:\[bot\])?',env.get('GITHUB_ACTOR','')):raise ValueError('CI actor required')
    if not 1<=len(env.get('GITHUB_TOKEN',''))<=4096:raise ValueError('ephemeral package publisher required')
    if not re.fullmatch(r'ghp_[A-Za-z0-9]{36}',env.get('SUITE_PACKAGE_READ_TOKEN','')):raise ValueError('separate package reader required')
    return {'commit':env['GITHUB_SHA'],'run':int(env['GITHUB_RUN_ID']),'attempt':int(env['GITHUB_RUN_ATTEMPT'])}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        raise ValueError('authenticated package API redirects refused')


def metadata_reader(token, opener=None):
    if not isinstance(token,str) or not re.fullmatch(r'ghp_[A-Za-z0-9]{36}',token):
        raise ValueError('classic read-only package credential required')
    opener=opener or urllib.request.build_opener(NoRedirect()).open
    request=urllib.request.Request('https://api.github.com/user',headers={'Authorization':'Bearer '+token,
        'Accept':'application/vnd.github+json','User-Agent':'rapadura-suite-reader/1'})
    with opener(request,timeout=30)as response:
        raw=response.read(65537)
        if response.status!=200 or len(raw)>65536:raise ValueError('package reader could not be verified')
        account=json.loads(raw)
        scopes={v.strip()for v in response.getheader('X-OAuth-Scopes','').split(',')if v.strip()}
    if not isinstance(account,dict) or account.get('login')!='rtio' or scopes!={'read:packages'}:
        raise ValueError('owner read:packages-only credential required')


def write_diagnostic(path, value):
    fd=os.open(path,os.O_WRONLY|os.O_CREAT|os.O_EXCL|os.O_NOFOLLOW,0o600)
    with os.fdopen(fd,'w')as stream:json.dump(value,stream,sort_keys=True);stream.write('\n')


def package(token, opener=None, diagnostic=None):
    opener=opener or urllib.request.build_opener(NoRedirect()).open
    request=urllib.request.Request(PACKAGE_URL,headers={'Authorization':'Bearer '+token,
        'Accept':'application/vnd.github+json','X-GitHub-Api-Version':'2022-11-28','User-Agent':'rapadura-suite-runtime/1'})
    try:
        with opener(request,timeout=30) as response:
            raw=response.read(1024**2+1)
            if response.status!=200 or len(raw)>1024**2:raise ValueError('bounded package metadata required')
            value=json.loads(raw)
    except urllib.error.HTTPError as error:
        print('Suite package metadata HTTP '+str(error.code),flush=True)
        if diagnostic is not None:write_diagnostic(diagnostic,{'format':'suite-package-diagnostic/1','http_status':error.code})
        if error.code==404:return None
        raise ValueError('package metadata unavailable') from None
    if not isinstance(value,dict)or not isinstance(value.get('repository'),dict):raise ValueError('repository-linked package metadata required')
    checks={'name_matches':value.get('name')=='community-suite-runtime','private':value.get('visibility')=='private',
        'repository_matches':value['repository'].get('id')==1415963195}
    print('Suite package checks: '+json.dumps(checks,sort_keys=True),flush=True)
    if diagnostic is not None:write_diagnostic(diagnostic,{'format':'suite-package-diagnostic/1','checks':checks})
    if not all(checks.values()):
        raise ValueError('private repository-linked Suite package required')
    return value


class CommandFailure(ValueError):
    def __init__(self, stage):
        self.stage=stage if stage in ['login','build','run','push','image'] else 'command'
        super().__init__('Suite CI '+self.stage+' failed; output withheld')


def diagnostic_tail(stream, secrets):
    if os.fstat(stream.fileno()).st_size>32*1024**2:return '[withheld: diagnostic exceeds budget]'
    stream.seek(0);value=stream.read().decode('utf-8',errors='replace')
    forms={form for secret in secrets if secret for form in
        [secret,base64.b64encode(secret.encode()).decode(),urllib.parse.quote(secret,safe='')]}
    for form in sorted(forms,key=len,reverse=True):value=value.replace(form,'[REDACTED]')
    return value[-16384:]


def command(argv, payload=None, *, env=None, timeout=120, output_limit=2*1024**2, diagnostic=None, secrets=()):
    stage=argv[1]if len(argv)>1 and argv[0]=='docker'else'command'
    failure=CommandFailure(stage)
    print('Suite runtime stage: '+failure.stage,flush=True)
    with tempfile.TemporaryFile() as output, tempfile.TemporaryFile() as error:
        timed_out=False;code=None
        try:code=subprocess.run(argv,input=payload,env=env,stdout=output,stderr=error,timeout=timeout).returncode
        except subprocess.TimeoutExpired:timed_out=True
        except OSError:pass
        if code!=0:
            if diagnostic is not None:
                value={'format':'suite-runtime-build-diagnostic/1','stage':failure.stage,
                    'returncode':code,'timed_out':timed_out,'stdout_tail':diagnostic_tail(output,secrets),
                    'stderr_tail':diagnostic_tail(error,secrets)}
                write_diagnostic(diagnostic,value)
            raise failure from None
        if os.fstat(output.fileno()).st_size>output_limit: raise ValueError('CI command output exceeds budget')
        output.seek(0);return output.read()


def publish(directory,binaries,env=None):
    env=dict(os.environ if env is None else env);run=identity(env)
    if not directory.is_absolute()or directory.exists()or directory.is_symlink():raise ValueError('fresh CI output directory required')
    metadata_reader(env['SUITE_PACKAGE_READ_TOKEN'])
    package(env['SUITE_PACKAGE_READ_TOKEN'])
    directory.mkdir(mode=0o700);context=directory/'context';hashes=build.prepare(context,binaries)
    tag=REPOSITORY+':runtime-'+run['commit']+'-'+str(run['run'])+'-'+str(run['attempt'])
    context_sha=sha(json.dumps(hashes,sort_keys=True).encode())
    with tempfile.TemporaryDirectory(prefix='suite-runtime-registry-')as registry:
        docker_env={k:v for k,v in env.items()if k not in ['GITHUB_TOKEN','GH_TOKEN','SUITE_PACKAGE_READ_TOKEN']}
        docker_env['DOCKER_CONFIG']=registry
        secrets=(env['GITHUB_TOKEN'],env['GITHUB_ACTOR']+':'+env['GITHUB_TOKEN'],env['SUITE_PACKAGE_READ_TOKEN'],'rtio:'+env['SUITE_PACKAGE_READ_TOKEN'])
        command(['docker','login','ghcr.io','--username',env['GITHUB_ACTOR'],'--password-stdin'],env['GITHUB_TOKEN'].encode(),env=docker_env)
        command(['docker','build','--label','org.opencontainers.image.source='+SOURCE,
            '--label','org.opencontainers.image.revision='+run['commit'],'--label','rapadura.net/suite-context-sha256='+context_sha,
            '--tag',tag,str(context)],env=docker_env,timeout=1200,output_limit=32*1024**2,
            diagnostic=directory/'build-diagnostic.json',secrets=secrets)
        result=command(['docker','run','--rm','-i','--network=none','--read-only','--user=999:999',
            '--cap-drop=ALL','--security-opt=no-new-privileges','--memory=256m','--pids-limit=64','--entrypoint=php',
            tag,'-r',VERIFY],json.dumps(hashes).encode(),env=docker_env)
        if result!=b'suite-runtime-verified':raise ValueError('Suite runtime verification failed')
        for args in [['nginx','-t'],['php-fpm','-t']]:
            command(['docker','run','--rm','--network=none','--read-only','--user=999:999','--cap-drop=ALL',
                '--security-opt=no-new-privileges','--memory=256m','--pids-limit=64',
                '--tmpfs','/tmp:rw,noexec,nosuid,size=16777216,mode=1777','--entrypoint',args[0],tag]+args[1:],env=docker_env)
        # Refusal is the fixed helper's expected no-argument result. Check it
        # inside a bounded, network-free image without accepting a shell verb.
        result=command(['docker','run','--rm','--network=none','--read-only','--user=999:999','--cap-drop=ALL',
            '--security-opt=no-new-privileges','--memory=128m','--pids-limit=32','--entrypoint','/bin/sh',tag,
            '-c','/usr/local/bin/suite-operations >/dev/null 2>&1; test "$?" = 65'],env=docker_env)
        info=json.loads(command(['docker','image','inspect',tag],env=docker_env))[0]
        config=info.get('Config')or{}
        if (config.get('User')!='999:999'or not re.fullmatch('sha256:[a-f0-9]{64}',info.get('Id',''))
                or config.get('Labels',{}).get('org.opencontainers.image.revision')!=run['commit']
                or config.get('Labels',{}).get('org.opencontainers.image.source')!=SOURCE
                or config.get('Labels',{}).get('rapadura.net/suite-context-sha256')!=context_sha):
            raise ValueError('qualified nonroot runtime image identity required')
        for entry in config.get('Env',[]):
            if entry.split('=',1)[0].startswith(('APP_','DB_','MAIL_','SESSION_','CACHE_','QUEUE_','REDIS_','AWS_','CLOUDFLARE_','ADMIN_','DEPLOYD_','GH_','GITHUB_','RESTIC_','KUBE')):
                raise ValueError('runtime configuration leaked into image')
        command(['docker','push',tag],env=docker_env,timeout=600)
        info=json.loads(command(['docker','image','inspect',tag],env=docker_env))[0]
        matches=[v for v in info.get('RepoDigests',[])if re.fullmatch(re.escape(REPOSITORY)+r'@sha256:[a-f0-9]{64}',v)]
        if len(matches)!=1:raise ValueError('one immutable runtime digest required')
        if package(env['SUITE_PACKAGE_READ_TOKEN'],diagnostic=directory/'package-diagnostic.json')is None:
            raise ValueError('published package not verified private and linked')
    receipt={'format':'suite-runtime-ci-publication/1',**run,'image':matches[0],'image_id':info['Id'],
        'context_files':hashes,'runtime_verified':True,'private_package_verified':True,'application_code_included':False,
        'configuration_included':False,'deployed':False,'profile':'measured-2-64'}
    (directory/'image-release.json').write_text(json.dumps(receipt,sort_keys=True)+'\n')
    (directory/'image-release.json').chmod(0o600)
    return receipt


def main():
    os.umask(0o077);parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--directory',type=Path,required=True)
    for name in ['operations','rapadura','deployd']:parser.add_argument('--'+name,type=Path,required=True)
    args=parser.parse_args()
    try:
        receipt=publish(args.directory,{'suite-operations':args.operations,'rapadura':args.rapadura,'deployd':args.deployd})
        print(json.dumps({k:receipt[k]for k in ['image','profile','private_package_verified','deployed']}));return 0
    except CommandFailure as error:parser.exit(1,'Suite CI runtime publication stopped during '+error.stage+'; diagnostics withheld\n')
    except Exception:parser.exit(1,'Suite CI runtime publication stopped; private diagnostics withheld\n')


if __name__=='__main__':raise SystemExit(main())
