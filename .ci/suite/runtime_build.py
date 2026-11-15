"""Data-free Suite runtime context; application releases and credentials are never inputs."""
import hashlib
from pathlib import Path

BASE=Path(__file__).resolve().parent
REPOSITORY='ghcr.io/rtio/community-suite-runtime'
RECIPES={'Dockerfile.runtime','nginx.conf','php-fpm.conf','artisan-apps.conf','worker.sh','service.sh'}
BINARIES={'suite-operations','rapadura','deployd'}


def prepare(destination,binaries):
    if not destination.is_absolute()or destination.exists()or destination.is_symlink()or set(binaries)!=BINARIES:
        raise ValueError('fresh context and exact runtime binaries required')
    files={}
    for name in sorted(RECIPES):
        source=BASE/name
        if source.is_symlink()or not source.is_file():raise ValueError('regular reviewed runtime recipe required')
        files['Dockerfile'if name=='Dockerfile.runtime'else name]=source.read_bytes()
    fpm=files['php-fpm.conf']
    if fpm.count(b'pm.max_children = 8\n')!=1:raise ValueError('reviewed measured FPM transformation required')
    files['php-fpm.conf']=fpm.replace(b'pm.max_children = 8\n',b'pm.max_children = 2\n')
    source=BASE.parent.parent/'suite/suite-deploy'
    if source.is_symlink()or not source.is_file():raise ValueError('reviewed deploy runner required')
    files['suite-deploy']=source.read_bytes()
    for name,path in binaries.items():
        path=Path(path)
        if path.is_symlink()or not path.is_file()or not 4096<=path.stat().st_size<=128*1024**2:
            raise ValueError('bounded regular CI executable required')
        blob=path.read_bytes()
        # ELF64, little-endian, Linux amd64. Never accept a host script or
        # silently package a macOS/Windows executable from a developer machine.
        if blob[:6]!=b'\x7fELF\x02\x01'or blob[18:20]!=b'\x3e\x00':raise ValueError('Linux amd64 ELF required')
        files[name]=blob
    destination.mkdir(mode=0o700)
    for name,blob in files.items():
        path=destination/name;path.write_bytes(blob);path.chmod(0o755 if name in BINARIES else 0o644)
    if {p.name for p in destination.iterdir()}!=set(files):raise ValueError('runtime context inventory differs')
    return {name:hashlib.sha256(blob).hexdigest()for name,blob in files.items()}
