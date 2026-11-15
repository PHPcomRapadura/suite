"""Copy only reviewed Python builder sources into the public Suite repository."""
import argparse
import hashlib
import json
from pathlib import Path
import sys
sys.dont_write_bytecode=True
import app_ci as ci

FILES={'app_ci.py','app_ci_test.py','app_image.py','app_image_test.py','app_vendor.py','app_vendor_test.py','runtime_publish.py','runtime_build.py','source-ci-baseline.yaml'}
FORMAT='suite-application-ci-vendor/1'


def verify(directory):
    path=directory/'manifest.json'
    if directory.is_symlink()or not directory.is_dir()or path.is_symlink()or not path.is_file()or path.stat().st_size>65536:
        raise ValueError('regular bounded Suite builder manifest required')
    value=ci.decoded(path.read_bytes(),65536)
    if (not isinstance(value,dict)or set(value)!={'format','files'}or value['format']!=FORMAT
            or not isinstance(value['files'],dict)or set(value['files'])!=FILES
            or {p.name for p in directory.iterdir()}!=FILES|{'manifest.json'}):
        raise ValueError('complete reviewed Suite builder required')
    for name,digest in value['files'].items():
        path=directory/name
        if path.is_symlink()or not path.is_file()or path.stat().st_size>1024**2 or not ci.digest(digest)or ci.sha(path.read_bytes())!=digest:
            raise ValueError('Suite builder copy changed')
    return value


def install(directory,base=None):
    base=base or Path(__file__).resolve().parent
    if directory.exists()or directory.is_symlink():raise ValueError('fresh builder destination required')
    files={}
    for name in sorted(FILES):
        path=base/name
        if path.is_symlink()or not path.is_file()or path.stat().st_size>1024**2:raise ValueError('regular bounded builder source required')
        files[name]=path.read_bytes()
    directory.mkdir(parents=True,mode=0o755)
    for name,payload in files.items():(directory/name).write_bytes(payload);(directory/name).chmod(0o644)
    value={'format':FORMAT,'files':{name:ci.sha(payload)for name,payload in files.items()}}
    (directory/'manifest.json').write_text(json.dumps(value,sort_keys=True,indent=2)+'\n')
    return verify(directory)


def workflow():
    return '''name: Build Suite image
on:
  workflow_call:
    secrets:
      IMAGE_PUBLISH_TOKEN:
        required: true
    inputs:
      revision: {type: string, required: true}
      source_run: {type: string, required: true}
      source_attempt: {type: string, required: true}
      artifact_id: {type: string, required: true}
      artifact_sha256: {type: string, required: true}
      parent_metadata: {type: string, required: false, default: ''}
  workflow_dispatch:
    inputs:
      revision: {type: string, required: true}
      source_run: {type: string, required: true}
      source_attempt: {type: string, required: true}
      artifact_id: {type: string, required: true}
      artifact_sha256: {type: string, required: true}
      parent_metadata: {type: string, required: false, default: ''}
permissions:
  contents: read
  actions: read
concurrency:
  group: suite-image-${{ github.run_id }}
  cancel-in-progress: false
jobs:
  publish:
    if: github.ref == 'refs/heads/main' && (github.event_name == 'push' || github.event_name == 'workflow_dispatch')
    runs-on: ubuntu-24.04
    timeout-minutes: 20
    environment: image-publication
    env:
      GH_TOKEN: ${{ github.token }}
      SUITE_REVISION: ${{ inputs.revision }}
      SUITE_SOURCE_RUN: ${{ inputs.source_run }}
      SUITE_SOURCE_ATTEMPT: ${{ inputs.source_attempt }}
      SUITE_ARTIFACT_ID: ${{ inputs.artifact_id }}
      SUITE_ARTIFACT_SHA256: ${{ inputs.artifact_sha256 }}
      SUITE_PARENT_METADATA: ${{ inputs.parent_metadata || vars.SUITE_PARENT_METADATA }}
    steps:
      - uses: actions/checkout@v4
      - name: Prepare bounded archive tooling
        run: |
          sudo apt-get update
          sudo apt-get install -y zstd
          command -v docker
          command -v gh
      - name: Verify reviewed builder copy
        run: python3 -B .ci/suite/app_vendor.py verify --directory .ci/suite
      - name: Check application publication boundaries
        run: |
          python3 -B .ci/suite/app_ci_test.py
          python3 -B .ci/suite/app_image_test.py
          python3 -B .ci/suite/app_vendor_test.py
      - name: Build and publish exact tested release
        env:
          SUITE_IMAGE_TOKEN: ${{ secrets.IMAGE_PUBLISH_TOKEN }}
        run: python3 -B .ci/suite/app_image.py --directory "$RUNNER_TEMP/suite-image"
      - name: Preserve bounded private failure diagnostics
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: suite-image-diagnostic-${{ github.run_id }}-${{ github.run_attempt }}
          path: ${{ runner.temp }}/suite-image/*-diagnostic.json
          if-no-files-found: ignore
      - uses: actions/upload-artifact@v4
        with:
          name: suite-image-${{ inputs.revision }}-${{ github.run_attempt }}
          path: ${{ runner.temp }}/suite-image/image-release.json
          if-no-files-found: error
'''


def source_workflow(text):
    """The reviewed old workflow must match before changing its artifact contract."""
    blob=hashlib.sha1(b'blob '+str(len(text.encode())).encode()+b'\0'+text.encode()).hexdigest()
    if blob!='225513cc88f173e0e7a5ccd14839d264f34ad392':raise ValueError('Suite workflow baseline changed; review required')
    def once(before,after):
        nonlocal text
        if text.count(before)!=1:raise ValueError('reviewed workflow anchor differs')
        text=text.replace(before,after,1)
    once('  build:\n    name: Build release\n    needs: quality',
        '  build:\n    name: Build release\n    outputs:\n      artifact_id: ${{ steps.release.outputs.artifact-id }}\n      artifact_sha256: ${{ steps.archive.outputs.sha256 }}\n      source_attempt: ${{ steps.archive.outputs.attempt }}\n    needs: quality')
    once('      - name: Package release\n        run:', '      - name: Package release\n        id: archive\n        run:')
    once('--exclude=./.git --exclude=./.github --exclude=./node_modules',
        '--exclude=./.git --exclude=./.github --exclude=./.ci --exclude=./node_modules')
    once('          sha256sum "$RUNNER_TEMP/suite-$GITHUB_SHA.tar.zst"',
        '          sha256sum "$RUNNER_TEMP/suite-$GITHUB_SHA.tar.zst"\n          echo "sha256=$(sha256sum "$RUNNER_TEMP/suite-$GITHUB_SHA.tar.zst" | cut -d\' \' -f1)" >> "$GITHUB_OUTPUT"\n          echo "attempt=$GITHUB_RUN_ATTEMPT" >> "$GITHUB_OUTPUT"')
    once('      - uses: actions/upload-artifact@v4\n', '      - uses: actions/upload-artifact@v4\n        id: release\n')
    name='name: suite-${{ github.sha }}'
    if text.count(name)!=2:raise ValueError('source release artifact consumers differ')
    text=text.replace(name,name+'-${{ github.run_attempt }}')
    once('      - uses: actions/download-artifact@v4\n        with:\n          name: suite-${{ github.sha }}-${{ github.run_attempt }}',
        '      - uses: actions/download-artifact@v4\n        with:\n          name: suite-${{ github.sha }}-${{ needs.build.outputs.source_attempt }}')
    once('  deploy-production:\n    name: Deploy production (VM)\n    needs: build',
        '''  build-image:
    name: Build CI image
    permissions:
      contents: read
      actions: read
    needs: build
    if: github.ref == 'refs/heads/main' && github.event_name != 'pull_request'
    uses: ./.github/workflows/container-image.yml
    secrets:
      IMAGE_PUBLISH_TOKEN: ${{ secrets.IMAGE_PUBLISH_TOKEN }}
    with:
      revision: ${{ github.sha }}
      source_run: ${{ github.run_id }}
      source_attempt: ${{ needs.build.outputs.source_attempt }}
      artifact_id: ${{ needs.build.outputs.artifact_id }}
      artifact_sha256: ${{ needs.build.outputs.artifact_sha256 }}

  deploy-production:
    name: Deploy production (VM)
    needs: [build, build-image]''')
    return text


def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('action',choices=['install','verify','workflow','source-workflow'])
    parser.add_argument('--directory',type=Path);parser.add_argument('--source',type=Path);args=parser.parse_args()
    if args.action=='workflow':print(workflow(),end='');return
    if args.action=='source-workflow':
        if args.source is None:parser.error('--source required')
        print(source_workflow(args.source.read_text()),end='');return
    if args.directory is None:parser.error('--directory required')
    value=install(args.directory)if args.action=='install'else verify(args.directory)
    print(json.dumps({'result':'verified','files':len(value['files'])}))


if __name__=='__main__':main()
