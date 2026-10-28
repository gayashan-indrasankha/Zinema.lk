"""No remote daemon is contacted; context metadata is simulated at this boundary."""
import json
from types import SimpleNamespace
import unittest
from unittest.mock import patch
import local


class LocalGuardTests(unittest.TestCase):
    def setUp(self):
        self.env=dict(local.ENV);self.checked=local._DOCKER_CHECKED
        local._DOCKER_CHECKED=False
        local.ENV.pop('DOCKER_HOST',None);local.ENV['DOCKER_CONTEXT']='fictional-context'

    def tearDown(self):
        local.ENV.clear();local.ENV.update(self.env);local._DOCKER_CHECKED=self.checked

    def test_host_override_rejected_before_any_docker_command(self):
        local.ENV['DOCKER_HOST']='tcp://production.example.invalid:2376'
        with patch.object(local.subprocess,'run',side_effect=AssertionError('Unexpected Docker command')):
            with self.assertRaises(RuntimeError):local.require_local_docker()

    def test_remote_context_rejected_without_daemon_access(self):
        metadata=SimpleNamespace(returncode=0,stdout=json.dumps([{'Endpoints':{'docker':{'Host':'ssh://example.invalid'}}}]))
        with patch.object(local.subprocess,'run',return_value=metadata) as runner:
            with self.assertRaises(RuntimeError):local.require_local_docker()
            self.assertEqual(runner.call_args.args[0],['docker','context','inspect','fictional-context'])

    def test_local_unix_and_named_pipe_contexts_are_pinned(self):
        for endpoint in ['unix:///var/run/docker.sock','npipe:////./pipe/dockerDesktopLinuxEngine']:
            local._DOCKER_CHECKED=False
            metadata=SimpleNamespace(returncode=0,stdout=json.dumps([{'Endpoints':{'docker':{'Host':endpoint}}}]))
            with patch.object(local.subprocess,'run',return_value=metadata) as runner:
                local.require_local_docker();local.require_local_docker()
                self.assertEqual(runner.call_count,1);self.assertEqual(local.ENV['DOCKER_CONTEXT'],'fictional-context')

    def test_broken_context_metadata_never_reaches_compose(self):
        with patch.object(local.subprocess,'run',return_value=SimpleNamespace(returncode=1,stdout='')) as runner:
            with self.assertRaises(RuntimeError):local.compose('up')
            self.assertEqual(runner.call_count,1)

    def test_compose_uses_only_the_explicit_safe_envfile(self):
        self.assertEqual(local.BASE[local.BASE.index('--env-file')+1],str(local.ROOT/'dev/compose.env'))
        self.assertTrue(all(line.startswith('#') or not line.strip() for line in (local.ROOT/'dev/compose.env').read_text().splitlines()))

    def test_unrelated_stopped_or_mismapped_gateways_are_rejected(self):
        local._DOCKER_CHECKED=True
        for running,project,address in [(False,local.PROJECT,'127.0.0.1'),(True,'unrelated','127.0.0.1'),(True,local.PROJECT,'0.0.0.0')]:
            info={'State':{'Running':running},'Config':{'Labels':{'com.docker.compose.project':project}},
                  'NetworkSettings':{'Ports':{'8088/tcp':[{'HostIp':address,'HostPort':'8088'}]}}}
            with patch.object(local.subprocess,'run',side_effect=[SimpleNamespace(returncode=0,stdout='fictional-gateway'),SimpleNamespace(returncode=0,stdout=json.dumps([info]))]):
                with self.assertRaises(RuntimeError):local.require_owned_gateway()

    def test_owned_running_loopback_gateway_is_accepted(self):
        local._DOCKER_CHECKED=True
        info={'State':{'Running':True},'Config':{'Labels':{'com.docker.compose.project':local.PROJECT}},
              'NetworkSettings':{'Ports':{'8088/tcp':[{'HostIp':'127.0.0.1','HostPort':'8088'}]}}}
        with patch.object(local.subprocess,'run',side_effect=[SimpleNamespace(returncode=0,stdout='fictional-gateway'),SimpleNamespace(returncode=0,stdout=json.dumps([info]))]):local.require_owned_gateway()


class ExtractorGuardTests(unittest.TestCase):
    def info(self,service):
        return {'Id':service+'-id','State':{'Running':True},'Config':{'Labels':{'com.docker.compose.project':local.PROJECT,'com.docker.compose.service':service},'Env':['APP_ENV=development','EXTRACTOR_MODE=local','ALLOW_LIVE_INTEGRATIONS=false']},'HostConfig':{'NetworkMode':'container:web-id','PortBindings':{}}}
    def check(self,node,web=None):
        results=[SimpleNamespace(returncode=0,stdout='web-id'),SimpleNamespace(returncode=0,stdout=json.dumps([web or self.info('web')])),SimpleNamespace(returncode=0,stdout='extractor-id'),SimpleNamespace(returncode=0,stdout=json.dumps([node]))]
        with patch.object(local,'require_owned_gateway'),patch.object(local.subprocess,'run',side_effect=results):local.require_owned_extractor()
    def test_exact_private_owned_fake_is_accepted(self):self.check(self.info('extractor'))
    def test_foreign_service_modes_or_namespace_fail_before_key_use(self):
        for field in ['foreign','stopped','mode','published','namespace']:
            node=self.info('extractor')
            if field=='foreign':node['Config']['Labels']['com.docker.compose.project']='unrelated'
            if field=='stopped':node['State']['Running']=False
            if field=='mode':node['Config']['Env']=['APP_ENV=production','EXTRACTOR_MODE=live','ALLOW_LIVE_INTEGRATIONS=true']
            if field=='published':node['HostConfig']['PortBindings']={'3334/tcp':[{}]}
            if field=='namespace':node['HostConfig']['NetworkMode']='host'
            with self.assertRaises(RuntimeError):self.check(node)


if __name__=='__main__':unittest.main()
