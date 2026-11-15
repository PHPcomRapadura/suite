"""Public builder copies and generated workflows, using a fixed public fixture."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import textwrap
import unittest
sys.dont_write_bytecode=True
import app_vendor as v


class Vendor(unittest.TestCase):
    def test_copy_is_complete_public_python_only_and_verifies_every_hash(self):
        with tempfile.TemporaryDirectory()as d:
            dest=Path(d)/'builder';value=v.install(dest)
            self.assertEqual(set(value['files']),v.FILES)
            self.assertTrue(all(name.endswith(('.py','.yaml'))for name in v.FILES))
            self.assertEqual(v.verify(dest),value)
            self.assertEqual({p.name for p in dest.iterdir()},v.FILES|{'manifest.json'})
            for name,digest in value['files'].items():
                self.assertEqual(hashlib.sha256((dest/name).read_bytes()).hexdigest(),digest)
                self.assertEqual((dest/name).stat().st_mode&0o777,0o644)
            with self.assertRaises(ValueError):v.install(dest)
            (dest/'app_image.py').write_text('changed')
            with self.assertRaises(ValueError):v.verify(dest)

    def test_extra_missing_symlink_and_manifest_changes_refuse(self):
        for mode in ['extra','missing','symlink','manifest']:
            with self.subTest(mode=mode),tempfile.TemporaryDirectory()as d:
                dest=Path(d)/'builder';v.install(dest)
                if mode=='extra':(dest/'secret.env').write_text('PRIVATE_FIXTURE')
                elif mode=='missing':(dest/'app_image.py').unlink()
                elif mode=='symlink':
                    (dest/'app_image.py').unlink();(dest/'app_image.py').symlink_to(dest/'app_ci.py')
                else:
                    value=json.loads((dest/'manifest.json').read_text());value['files'].pop('app_image.py')
                    (dest/'manifest.json').write_text(json.dumps(value))
                with self.assertRaises(ValueError):v.verify(dest)

    def test_copy_runs_its_tests_standalone_without_the_private_operator_checkout(self):
        with tempfile.TemporaryDirectory()as d:
            dest=Path(d)/'builder';v.install(dest)
            result=subprocess.run([sys.executable,'-B',str(dest/'app_ci_test.py')],cwd=d,capture_output=True,timeout=15)
            self.assertEqual(result.returncode,0,result.stderr.decode())

    def test_reusable_publisher_is_main_only_and_has_no_production_credentials(self):
        workflow=v.workflow()
        for required in ['workflow_call:','workflow_dispatch:',"github.ref == 'refs/heads/main'",
                'timeout-minutes: 20','environment: image-publication','actions: read','secrets.IMAGE_PUBLISH_TOKEN',
                'vars.SUITE_PARENT_METADATA','sudo apt-get install -y zstd','app_vendor.py verify','app_image.py --directory']:
            self.assertIn(required,workflow)
        for forbidden in ['contents: write','packages: write','secrets: inherit','DEPLOY_HOOK_SECRET',
                'DEPLOY_SERVER_PRIVATE_KEY','kubectl','rapadura-secrets','docker.sock','self-hosted']:
            self.assertNotIn(forbidden,workflow)

    def test_source_patch_binds_release_outputs_and_image_dependency_preserving_other_jobs(self):
        baseline=(Path(__file__).with_name('source-ci-baseline.yaml')).read_text()
        result=v.source_workflow(baseline)
        self.assertIn('name: suite-${{ github.sha }}-${{ github.run_attempt }}',result)
        self.assertIn('needs: [build, build-image]',result)
        self.assertIn('  build-image:\n    name: Build CI image\n    permissions:\n      contents: read\n      actions: read\n',result)
        self.assertIn('source_attempt: '+chr(36)+'{{ needs.build.outputs.source_attempt }}',result)
        self.assertIn('name: suite-'+chr(36)+'{{ github.sha }}-'+chr(36)+'{{ needs.build.outputs.source_attempt }}',result)
        self.assertIn('artifact_id: ${{ needs.build.outputs.artifact_id }}',result)
        self.assertIn('artifact_sha256: ${{ needs.build.outputs.artifact_sha256 }}',result)
        self.assertIn('--exclude=./.ci',result)
        self.assertEqual(result[result.index('  deploy-staging:'):],baseline[baseline.index('  deploy-staging:'):])
        # Production still uses its existing signed feed until the operator cutover.
        for marker in ['      # The receiver checks:', '      - name: Wait for the release to go live']:
            end='  deploy-staging:'if marker.endswith('live')else'      - name: Wait for the release to go live'
            self.assertEqual(result[result.index(marker):result.index(end)],baseline[baseline.index(marker):baseline.index(end)])
        with self.assertRaises(ValueError):v.source_workflow(result)
        with self.assertRaises(ValueError):v.source_workflow(baseline+'# foreign change\n')

    def test_environment_publisher_is_required_and_forwarded_by_name_only(self):
        baseline=Path(__file__).with_name('source-ci-baseline.yaml').read_text()
        result=v.source_workflow(baseline);mark=chr(36)
        self.assertIn('    secrets:\n      IMAGE_PUBLISH_TOKEN: '+mark+'{{ secrets.IMAGE_PUBLISH_TOKEN }}',result)
        self.assertIn('    secrets:\n      IMAGE_PUBLISH_TOKEN:\n        required: true',v.workflow())
        self.assertNotIn('secrets: inherit',result+v.workflow())

    def test_generated_package_script_reports_actual_checksum_and_excludes_builder(self):
        baseline=Path(__file__).with_name('source-ci-baseline.yaml').read_text();result=v.source_workflow(baseline)
        start=result.index('      - name: Package release\n');end=result.index('\n      - uses: actions/upload-artifact@v4',start)
        script=textwrap.dedent(result[start:end].split('        run: |\n',1)[1])
        with tempfile.TemporaryDirectory()as d:
            root=Path(d);tools=root/'bin';tools.mkdir();out=root/'outputs';tar_args=root/'tar-args.json'
            tar=tools/'tar';tar.write_text('#!'+sys.executable+'\nimport json,os,sys\nfrom pathlib import Path\n'
                'Path(os.environ["TAR_ARGS"]).write_text(json.dumps(sys.argv[1:]))\n'
                'Path(sys.argv[sys.argv.index("-cf")+1]).write_bytes(b"release-fixture")\n');tar.chmod(0o755)
            digest=tools/'sha256sum';digest.write_text('#!'+sys.executable+'\nimport hashlib,sys\nfrom pathlib import Path\n'
                'print(hashlib.sha256(Path(sys.argv[1]).read_bytes()).hexdigest()+"  "+sys.argv[1])\n');digest.chmod(0o755)
            env=os.environ.copy();env.update({'PATH':str(tools)+os.pathsep+env['PATH'],'RUNNER_TEMP':d,
                'GITHUB_SHA':'b'*40,'GITHUB_RUN_ATTEMPT':'2','GITHUB_OUTPUT':str(out),'TAR_ARGS':str(tar_args)})
            proc=subprocess.run(['/bin/bash','-eu','-c',script],cwd=d,env=env,capture_output=True,timeout=10)
            self.assertEqual(proc.returncode,0,proc.stderr.decode())
            self.assertEqual(out.read_text(),'sha256='+hashlib.sha256(b'release-fixture').hexdigest()+'\nattempt=2\n')
            args=json.loads(tar_args.read_text());self.assertIn('--exclude=./.ci',args);self.assertIn('--exclude=./.env',args)


if __name__=='__main__':unittest.main()
