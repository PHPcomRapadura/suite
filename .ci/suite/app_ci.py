"""Bind Suite application images to tested main artifacts and a qualified runtime."""
import copy
import datetime
import hashlib
import io
import json
from pathlib import Path
import re
import resource
import stat
import subprocess
import tarfile
import tempfile
import zipfile

SOURCE='PHPcomRapadura/suite'
SOURCE_ID=1257563657
PARENT='ghcr.io/rtio/community-suite-runtime'
IMAGE='ghcr.io/rtio/community-suite'
MAX_COMPRESSED=128*1024**2
MAX_TAR=512*1024**2
ROOTS={'app','bootstrap','config','database','lang','public','resources','routes','vendor'}
TOP={'artisan','composer.json','composer.lock','package.json','package-lock.json','REVISION'}
PARENT_FILES={'Dockerfile','nginx.conf','php-fpm.conf','artisan-apps.conf','worker.sh','service.sh','suite-deploy','suite-operations','rapadura','deployd'}
PARENT_FIELDS={'format','commit','run','attempt','image','image_id','context_files','runtime_verified','private_package_verified','application_code_included','configuration_included','deployed','profile'}
FIELDS={'format','revision','source_run','source_attempt','artifact_id','artifact_sha256','parent'}


def sha(blob):return hashlib.sha256(blob).hexdigest()


def decoded(blob,limit=1024**2):
    if not isinstance(blob,bytes)or not 0<len(blob)<=limit:raise ValueError('bounded JSON required')
    def pairs(rows):
        result={}
        for key,value in rows:
            if key in result:raise ValueError('duplicate JSON field')
            result[key]=value
        return result
    return json.loads(blob,object_pairs_hook=pairs)


def positive(value):return type(value)is int and 0<value<2**63


def digest(value):return isinstance(value,str)and re.fullmatch('[a-f0-9]{64}',value)and value!='0'*64


def parent(value):
    if (not isinstance(value,dict)or set(value)!=PARENT_FIELDS
            or value.get('format')!='suite-runtime-ci-publication/1'or value.get('profile')!='measured-2-64'
            or not re.fullmatch('[a-f0-9]{40}',str(value.get('commit','')))
            or any(not positive(value.get(k))for k in ['run','attempt'])
            or not isinstance(value.get('image'),str)or not re.fullmatch(re.escape(PARENT)+r'@sha256:[a-f0-9]{64}',value['image'])
            or not isinstance(value.get('image_id'),str)or not re.fullmatch(r'sha256:[a-f0-9]{64}',value['image_id'])
            or any(value.get(k)is not True for k in ['runtime_verified','private_package_verified'])
            or any(value.get(k)is not False for k in ['application_code_included','configuration_included','deployed'])
            or not isinstance(value.get('context_files'),dict)or set(value['context_files'])!=PARENT_FILES
            or any(not digest(v)for v in value['context_files'].values())):
        raise ValueError('complete qualified data-free runtime receipt required')
    return copy.deepcopy(value)


def checked(value):
    if (not isinstance(value,dict)or set(value)!=FIELDS or value.get('format')!='suite-application-ci-request/1'
            or not isinstance(value.get('revision'),str)or not re.fullmatch('[a-f0-9]{40}',value['revision'])
            or any(not positive(value.get(k))for k in ['source_run','source_attempt','artifact_id'])
            or not digest(value.get('artifact_sha256'))):
        raise ValueError('complete Suite source artifact identity required')
    parent(value.get('parent'))
    return copy.deepcopy(value)


def timestamp(value):
    if not isinstance(value,str):raise ValueError('CI timestamp required')
    result=datetime.datetime.fromisoformat(value.replace('Z','+00:00'))
    if result.tzinfo is None:raise ValueError('CI timestamp timezone required')
    return result


def source(request,api):
    request=checked(request)
    repo=api('')
    if any(type(repo.get(k))is not type(v)or repo[k]!=v for k,v in {
            'id':SOURCE_ID,'full_name':SOURCE,'private':False,'default_branch':'main'}.items()):
        raise ValueError('Suite source repository differs')
    workflow=api('actions/workflows/ci.yaml')
    if (not positive(workflow.get('id'))or workflow.get('name')!='CI/CD'
            or workflow.get('path')!='.github/workflows/ci.yaml'or workflow.get('state')!='active'):
        raise ValueError('Suite source workflow differs')
    run=api('actions/runs/'+str(request['source_run']))
    if (any(run.get(k)!=v for k,v in {'id':request['source_run'],'head_sha':request['revision'],
            'head_branch':'main','workflow_id':workflow['id'],'path':workflow['path']}.items())
            or run.get('event')not in ['push','workflow_dispatch']
            or not positive(run.get('run_attempt'))or request['source_attempt']>run['run_attempt']
            or any(run.get(k,{}).get('id')!=SOURCE_ID for k in ['repository','head_repository'])):
        raise ValueError('Suite source execution differs')
    compare=api('compare/'+request['revision']+'...main')
    if compare.get('status')not in ['ahead','identical']or compare.get('merge_base_commit',{}).get('sha')!=request['revision']:
        raise ValueError('Suite revision is not on main')
    def checks(attempt,complete):
        response=api(f"actions/runs/{run['id']}/attempts/{attempt}/jobs?per_page=100")
        jobs=response.get('jobs',[])
        if (not jobs or type(response.get('total_count'))is not int or response['total_count']!=len(jobs)
                or len(jobs)>100 or len({j.get('name')for j in jobs})!=len(jobs)):
            raise ValueError('complete source check inventory required')
        chosen={j['name']:j for j in jobs if j.get('name')in ['Quality','Build release']}
        if complete and set(chosen)!={'Quality','Build release'}:raise ValueError('quality and release prerequisites required')
        for job in chosen.values():
            if any(job.get(k)!=v for k,v in {'head_sha':request['revision'],'run_id':run['id'],'status':'completed','conclusion':'success'}.items()):
                raise ValueError('Suite source prerequisite not successful')
            if timestamp(job['started_at'])>timestamp(job['completed_at']):raise ValueError('source check timestamps differ')
        return chosen
    original=checks(request['source_attempt'],True)
    if request['source_attempt']<run['run_attempt']:checks(run['run_attempt'],False)
    artifact=api('actions/artifacts/'+str(request['artifact_id']))
    if (any(artifact.get(k)!=v for k,v in {'id':request['artifact_id'],'expired':False,
            'name':f"suite-{request['revision']}-{request['source_attempt']}"}.items())
            or artifact.get('expired')is not False
            or not re.fullmatch('sha256:[a-f0-9]{64}',str(artifact.get('digest','')))
            or type(artifact.get('size_in_bytes'))is not int or not 0<artifact['size_in_bytes']<=MAX_COMPRESSED+1024**2
            or any(artifact.get('workflow_run',{}).get(k)!=v for k,v in {'id':run['id'],'repository_id':SOURCE_ID,
                'head_repository_id':SOURCE_ID,'head_sha':request['revision'],'head_branch':'main'}.items())):
        raise ValueError('Suite release artifact differs')
    build=original['Build release']
    if not timestamp(build['started_at'])<=timestamp(artifact['created_at'])<=timestamp(build['completed_at']):
        raise ValueError('artifact is outside its release job execution')
    return {'repository':SOURCE,'run':run['id'],'attempt':request['source_attempt'],'artifact':artifact['id'],'artifact_zip_sha256':artifact['digest'][7:]}


def envelope(request,payload):
    request=checked(request)
    if not isinstance(payload,bytes)or not 0<len(payload)<=MAX_COMPRESSED+1024**2:raise ValueError('bounded CI artifact required')
    with zipfile.ZipFile(io.BytesIO(payload))as archive:
        rows=archive.infolist()
        if len(rows)!=1:raise ValueError('one compressed Suite release required')
        row=rows[0]
        if (row.filename!=f"suite-{request['revision']}.tar.zst"or row.is_dir()or row.flag_bits&1
                or stat.S_IFMT(row.external_attr>>16)not in [0,stat.S_IFREG]or not 0<row.file_size<=MAX_COMPRESSED):
            raise ValueError('Suite release envelope refused')
        result=archive.read(row)
    if sha(result)!=request['artifact_sha256']:raise ValueError('Suite release checksum differs')
    return result


def decompress(payload):
    if not isinstance(payload,bytes)or not 0<len(payload)<=MAX_COMPRESSED:raise ValueError('bounded compressed release required')
    with tempfile.TemporaryFile()as output,tempfile.TemporaryFile()as errors:
        def limit():resource.setrlimit(resource.RLIMIT_FSIZE,(MAX_TAR,MAX_TAR))
        result=subprocess.run(['zstd','--decompress','--stdout','--threads=1'],input=payload,
            stdout=output,stderr=errors,timeout=120,preexec_fn=limit)
        if result.returncode or not 0<output.tell()<=MAX_TAR:raise ValueError('bounded zstd release required')
        output.seek(0);return output.read()


def unpack(blob,revision,with_modes=False):
    if not re.fullmatch('[a-f0-9]{40}',revision)or not isinstance(blob,bytes)or not 0<len(blob)<=MAX_TAR:
        raise ValueError('bounded exact-revision tar required')
    files={};modes={};seen=set();total=0
    with tarfile.open(fileobj=io.BytesIO(blob),mode='r|')as archive:
        for member in archive:
            name=member.name
            if name.startswith('./'):name=name[2:]
            if name in ['', '.']and member.isdir():continue
            parts=name.split('/')
            if (name in seen or len(seen)>=100000 or len(name.encode())>1024
                    or '\\'in name or any(ord(c)<32 or ord(c)==127 for c in name)
                    or any(p in ['', '.', '..']for p in parts)
                    or not(member.isdir()or member.isfile())or member.mode&0o7000):
                raise ValueError('regular canonical archive entries required')
            seen.add(name)
            if member.isdir():continue
            if not 0<=member.size<=32*1024**2:raise ValueError('release file exceeds budget')
            total+=member.size
            if total>MAX_TAR:raise ValueError('release payload exceeds budget')
            if parts[0].lower().startswith('.env')and name!='.env.example':raise ValueError('release contains runtime environment')
            if parts[0]not in ROOTS and name not in TOP:continue
            if name.startswith(('bootstrap/cache/','public/storage/'))or name=='public/storage':continue
            if any(p.lower().startswith('.env')or p.lower()in {'.git','auth.json','.npmrc'}
                    or p.lower().endswith(('.sql','.sqlite','.sqlite3','.db','-wal','-shm'))for p in parts):
                raise ValueError('runtime state in application code')
            payload=archive.extractfile(member).read(member.size+1)
            if len(payload)!=member.size or payload.startswith(b'SQLite format 3\0'):raise ValueError('database or truncated release file')
            files[name]=payload;modes[name]=0o644|(member.mode&0o111)
    if not {'artisan','composer.lock','public/index.php','REVISION'}<=set(files):raise ValueError('incomplete Suite release')
    if files['REVISION']!=(revision+'\n').encode():raise ValueError('Suite release revision differs')
    return (files,modes)if with_modes else files


def seed(revision):
    if not re.fullmatch('[a-f0-9]{40}',revision):raise ValueError('literal seed revision required')
    return ('''#!/bin/sh
set -eu
base=/suite
sha=@REVISION@
verify() {
 links=$(find "$base/releases/$sha" -type l ! -path "$base/releases/$sha/.env" ! -path "$base/releases/$sha/storage" -printf x -quit) || return 65
 [ -z "$links" ] || return 65
 for name in .env storage; do
  if [ -e "$base/releases/$sha/$name" ] || [ -L "$base/releases/$sha/$name" ]; then
   [ -L "$base/releases/$sha/$name" ] && [ "$(readlink "$base/releases/$sha/$name")" = "$base/shared/$name" ] || return 65
  fi
 done
 (cd "$base/releases/$sha" && sha256sum --status -c /usr/local/share/suite-code.sha256)
}
[ ! -L "$base/releases" ] && [ ! -L "$base/shared" ] || exit 65
if [ -e "$base/current" ] || [ -L "$base/current" ]; then
 [ -L "$base/current" ] && [ "$(readlink "$base/current")" = "releases/$sha" ] || exit 65
 [ -d "$base/releases/$sha" ] && [ ! -L "$base/releases/$sha" ] || exit 65
 [ -L "$base/releases/$sha/.env" ] && [ -L "$base/releases/$sha/storage" ] || exit 65
 verify
 exit 0
fi
[ ! -e "$base/releases/$sha" ] && [ ! -L "$base/releases/$sha" ] || exit 65
[ -f "$base/shared/.env" ] && [ ! -L "$base/shared/.env" ] || exit 65
mkdir -p "$base/releases" "$base/shared/storage" "$base/shared/db-snapshots"
cp -R /seed "$base/releases/$sha"
verify
ln -s "$base/shared/storage" "$base/releases/$sha/storage"
ln -s "$base/shared/.env" "$base/releases/$sha/.env"
mkdir -p "$base/releases/$sha/bootstrap/cache"
ln -s "releases/$sha" "$base/current"
''').replace('@REVISION@',revision)


def prepare(request,blob,destination):
    request=checked(request)
    if not destination.is_absolute()or destination.exists()or destination.is_symlink():raise ValueError('fresh absolute application context required')
    files,modes=unpack(blob,request['revision'],with_modes=True);hashes={name:sha(payload)for name,payload in files.items()}
    manifest={'format':'suite-code-manifest/1','revision':request['revision'],'files':hashes,'modes':modes}
    manifest_blob=(json.dumps(manifest,sort_keys=True)+'\n').encode()
    docker=f'''FROM {request['parent']['image']}
USER 0:0
COPY --chown=999:999 seed/ /seed/
COPY code-manifest.json /usr/local/share/suite-code-manifest.json
COPY code.sha256 /usr/local/share/suite-code.sha256
COPY seed.sh /usr/local/bin/suite-seed
RUN chmod 0755 /usr/local/bin/suite-seed
USER 999:999
'''
    destination.mkdir(mode=0o700)
    for name,payload in files.items():
        path=destination/'seed'/name;path.parent.mkdir(parents=True,exist_ok=True,mode=0o755)
        path.write_bytes(payload);path.chmod(modes[name])
    for path in (destination/'seed').rglob('*'):
        if path.is_dir():path.chmod(0o755)
    (destination/'seed').chmod(0o755)
    context={'Dockerfile':docker.encode(),'seed.sh':seed(request['revision']).encode(),'code-manifest.json':manifest_blob,
        'code.sha256':''.join(f'{digest}  {name}\n'for name,digest in sorted(hashes.items())).encode()}
    for name,payload in context.items():(destination/name).write_bytes(payload);(destination/name).chmod(0o644)
    return {'format':'suite-application-context/1','revision':request['revision'],'parent':request['parent']['image'],
        'artifact_sha256':request['artifact_sha256'],'code_manifest_sha256':sha(manifest_blob),'code_files':len(files),
        'context_files':{name:sha(payload)for name,payload in context.items()},'runtime_configuration_included':False}
