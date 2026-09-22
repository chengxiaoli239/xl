import importlib.util
import json
import os
from pathlib import Path
import socketserver
import subprocess
import tempfile
import threading
import http.client
import unittest
from unittest.mock import patch

import yaml

spec = importlib.util.spec_from_file_location('node_manager', Path(__file__).resolve().parents[1] / 'deploy/mihomo/node_manager.py')
nm = importlib.util.module_from_spec(spec)
spec.loader.exec_module(nm)


class InputTests(unittest.TestCase):
    def test_yaml_ignores_global_config(self):
        data = 'tun: {enable: true}\ndns: {enable: true}\nport: 7890\nproxies:\n- {name: HK, type: http, server: example.com, port: 443}'
        self.assertEqual(len(nm.parse_nodes(data)), 1)
        self.assertNotIn('tun', nm.parse_nodes(data)[0])

    def test_alias_tags_depth_size(self):
        for content in ['proxies: &x [*x]', '!!python/object/apply:os.system [id]', '[' * 20 + ']' * 20, 'a' * (nm.MAX_BYTES + 1)]:
            with self.subTest(content=content[:20]), self.assertRaises(ValueError):
                nm.parse_nodes(content)

    def test_protocol_and_sensitive_file_keys(self):
        normal = {'name': 'HK', 'type': 'vless', 'server': 'example.com', 'port': 443, 'uuid': 'fake'}
        self.assertEqual(nm.validate_node(normal), normal)
        for extra in [{'dialer-proxy': 'DIRECT'}, {'certificate': '/etc/passwd'}, {'type': 'direct'},
                      {'type': 'reject'}, {'ws-opts': {'unknown': 'bad'}}, {'port': True}]:
            with self.subTest(extra=extra), self.assertRaises(ValueError):
                nm.validate_node(dict(normal, **extra))

    def test_subscription_ssrf(self):
        for url in ['http://example.com/a', 'file:///etc/passwd', 'https://u:p@example.com/a',
                    'https://example.com:8443/a', 'https://127.0.0.1/a', 'https://169.254.169.254/a', 'https://[::1]/a']:
            with self.subTest(url=url), self.assertRaises(ValueError):
                nm.fetch_subscription(url)
        with patch.object(nm.socket, 'getaddrinfo', return_value=[(2, 1, 6, '', ('10.1.1.1', 443))]):
            with self.assertRaises(ValueError):
                nm.public_addresses('dns-rebind.example', 443)

    def test_failed_import_does_not_change_existing(self):
        with tempfile.TemporaryDirectory() as directory:
            manager = nm.Manager(directory, '/usr/bin/false', validate_address=lambda *a: ['127.0.0.1'])
            with self.assertRaises(ValueError):
                manager.import_selected('proxies: [{name: HK, type: http, server: example.com, port: 443}]', [0])
            self.assertEqual(manager.nodes, [])
            self.assertFalse(manager.registry_path.exists())

    def test_runtime_dns_pin_and_orphan_port_not_reused(self):
        with tempfile.TemporaryDirectory() as directory:
            orphan = Path(directory) / str(nm.FIRST_PORT)
            orphan.mkdir()
            (orphan / 'config.json').write_text('orphan-must-not-change')
            manager = nm.Manager(directory, '/usr/bin/true', validate_address=lambda *a: ['1.1.1.1'])
            node = {'name': 'HK', 'type': 'vless', 'server': 'rebind.example', 'port': 443,
                    'uuid': 'fake', 'tls': True, 'network': 'ws'}
            manager.import_selected(yaml.safe_dump({'proxies': [node]}), [0])
            self.assertEqual(manager.nodes[0]['port'], nm.FIRST_PORT + 1)
            self.assertEqual((orphan / 'config.json').read_text(), 'orphan-must-not-change')
            config = json.loads((Path(directory) / str(nm.FIRST_PORT + 1) / 'config.json').read_text())
            self.assertEqual(config['proxies'][0]['server'], '1.1.1.1')
            self.assertEqual(config['proxies'][0]['servername'], 'rebind.example')
            self.assertEqual(config['proxies'][0]['ws-opts']['headers']['Host'], 'rebind.example')

    def test_authenticated_preview_hides_credentials_and_expired_import(self):
        with tempfile.TemporaryDirectory() as directory:
            server = nm.HTTPServer(('127.0.0.1', 0), nm.Handler)
            server.manager = nm.Manager(directory, '/usr/bin/false')
            server.secret, server.preview = 'x' * 40, ('', 0, '')
            thread = threading.Thread(target=server.serve_forever, daemon=True)
            thread.start()
            try:
                def request(path, payload, authorized=True):
                    connection = http.client.HTTPConnection(*server.server_address, timeout=3)
                    headers = {'Content-Type': 'application/json'}
                    if authorized:
                        headers['Authorization'] = 'Bearer ' + server.secret
                    connection.request('POST', path, json.dumps(payload), headers)
                    response = connection.getresponse()
                    status, body = response.status, response.read().decode()
                    connection.close()
                    return status, body
                self.assertEqual(request('/preview', {}, False)[0], 403)
                content = yaml.safe_dump({'proxies': [{'name': '<script>HK</script>', 'type': 'trojan',
                    'server': 'example.com', 'port': 443, 'password': 'SYNTHETIC-SECRET'}]})
                status, body = request('/preview', {'content': content})
                self.assertEqual(status, 200)
                self.assertNotIn('SYNTHETIC-SECRET', body)
                self.assertNotIn('example.com', body)
                self.assertIn('ticket', json.loads(body))
                self.assertEqual(request('/import', {'ticket': 'wrong', 'indexes': [0]})[0], 400)
                self.assertEqual(server.manager.nodes, [])
            finally:
                server.shutdown()
                server.server_close()


class Upstream(socketserver.StreamRequestHandler):
    def handle(self):
        line = self.rfile.readline()
        while self.rfile.readline() not in (b'\r\n', b'\n', b''):
            pass
        if line.startswith(b'CONNECT '):
            self.wfile.write(b'HTTP/1.1 200 Connection Established\r\n\r\n')
            self.wfile.flush()
            line = self.rfile.readline()
            while self.rfile.readline() not in (b'\r\n', b'\n', b''):
                pass
        if line:
            self.server.requests += 1
            marker = self.server.marker
            self.wfile.write(b'HTTP/1.1 200 OK\r\nConnection: close\r\nContent-Length: ' + str(len(marker)).encode() + b'\r\n\r\n' + marker)


class ThreadServer(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True


@unittest.skipUnless(os.environ.get('MIHOMO_BINARY'), 'Set MIHOMO_BINARY for isolated real-kernel tests')
class KernelTests(unittest.TestCase):
    def test_independent_nodes_import_dedup_restart_and_failure(self):
        servers = []
        manager = None
        with tempfile.TemporaryDirectory() as directory:
            try:
                for marker in [b'NODE-A', b'NODE-B']:
                    server = ThreadServer(('127.0.0.1', 0), Upstream)
                    server.requests, server.marker = 0, marker
                    threading.Thread(target=server.serve_forever, daemon=True).start()
                    servers.append(server)
                nodes = [{'name': 'HK '+str(i), 'type': 'http', 'server': '127.0.0.1', 'port': s.server_address[1]} for i, s in enumerate(servers)]
                content = yaml.safe_dump({'tun': {'enable': True}, 'proxies': nodes})
                manager = nm.Manager(directory, os.environ['MIHOMO_BINARY'], validate_address=lambda *a: ['127.0.0.1'])
                manager.import_selected(content, [0])
                self.assertFalse(manager.listing()[0]['running'])
                manager.start(nm.FIRST_PORT)
                pid_a = manager.processes[nm.FIRST_PORT].pid
                def fetch(port):
                    return subprocess.run(['curl', '-fsS', '--max-time', '3', '--noproxy', '', '--proxy',
                        'http://127.0.0.1:'+str(port), 'http://never-direct.invalid/test'], capture_output=True)
                self.assertEqual(fetch(nm.FIRST_PORT).stdout, b'NODE-A')
                manager.import_selected(content, [0, 1])
                self.assertEqual(len(manager.nodes), 2)
                self.assertEqual(manager.processes[nm.FIRST_PORT].pid, pid_a)
                manager.start(nm.FIRST_PORT + 1)
                pid_b = manager.processes[nm.FIRST_PORT + 1].pid
                self.assertEqual(fetch(nm.FIRST_PORT + 1).stdout, b'NODE-B')
                self.assertEqual(fetch(nm.FIRST_PORT).stdout, b'NODE-A')
                manager.processes[nm.FIRST_PORT].terminate()
                manager.processes[nm.FIRST_PORT].wait(timeout=5)
                self.assertNotEqual(fetch(nm.FIRST_PORT).returncode, 0)
                self.assertEqual(fetch(nm.FIRST_PORT + 1).stdout, b'NODE-B')
                manager.start(nm.FIRST_PORT)
                self.assertEqual(manager.processes[nm.FIRST_PORT + 1].pid, pid_b)
                self.assertEqual(fetch(nm.FIRST_PORT).stdout, b'NODE-A')
                config = json.loads((Path(directory) / str(nm.FIRST_PORT) / 'config.json').read_text())
                self.assertNotIn('tun', config)
                self.assertEqual(config['rules'], ['MATCH,UPSTREAM'])
                self.assertNotIn('password', json.dumps(manager.listing()))
                self.assertEqual((manager.registry_path.stat().st_mode & 0o777), 0o600)
            finally:
                if manager:
                    manager.close()
                for server in servers:
                    server.shutdown()
                    server.server_close()


if __name__ == '__main__':
    unittest.main()
