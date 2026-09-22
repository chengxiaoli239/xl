#!/usr/bin/env python3
"""Private loopback-only node manager. Never imports subscription routing rules."""
import argparse
import hashlib
import hmac
import http.client
import ipaddress
import json
import os
from pathlib import Path
import signal
import socket
import ssl
import subprocess
import tempfile
import time
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlsplit

import yaml

MAX_BYTES = 1024 * 1024
FIRST_PORT = 18100
MAX_NODES = 16
MAX_RUNNING = 8
TYPES = {'ss', 'vmess', 'vless', 'trojan', 'hysteria2', 'http', 'socks5'}
FIELDS = {
    'name', 'type', 'server', 'port', 'cipher', 'password', 'uuid', 'alterId',
    'tls', 'servername', 'sni', 'alpn', 'network', 'client-fingerprint',
    'fingerprint', 'flow', 'username', 'udp', 'obfs', 'obfs-password',
    'up', 'down', 'skip-cert-verify', 'reality-opts', 'ws-opts', 'grpc-opts',
}
NESTED_FIELDS = {
    'reality-opts': {'public-key', 'short-id'},
    'ws-opts': {'path', 'headers', 'max-early-data', 'early-data-header-name'},
    'grpc-opts': {'grpc-service-name'},
}


def public_addresses(host, port):
    addresses = list(dict.fromkeys(item[4][0] for item in socket.getaddrinfo(host, port, type=socket.SOCK_STREAM)))
    if not addresses or any(not ipaddress.ip_address(ip).is_global for ip in addresses):
        raise ValueError('地址必须解析到公网 IP')
    return addresses


def fetch_subscription(url):
    """HTTPS only, DNS-pinned connection, no redirects, no system proxy or URL logging."""
    try:
        parts = urlsplit(url)
        if (parts.scheme != 'https' or not parts.hostname or parts.username or parts.password
                or parts.fragment or parts.port not in (None, 443) or len(url) > 4096):
            raise ValueError()
        host = parts.hostname.encode('idna').decode('ascii')
        address = public_addresses(host, 443)[0]
        connection = http.client.HTTPSConnection(host, timeout=12, context=ssl.create_default_context())
        raw = socket.create_connection((address, 443), timeout=12)
        try:
            connection.sock = ssl.create_default_context().wrap_socket(raw, server_hostname=host)
        except Exception:
            raw.close()
            raise
        try:
            path = parts.path or '/'
            if parts.query:
                path += '?' + parts.query
            connection.request('GET', path, headers={'User-Agent': 'Clash.Meta', 'Accept-Encoding': 'identity'})
            response = connection.getresponse()
            if response.status != 200:
                raise ValueError()
            content = bytearray()
            deadline = time.monotonic() + 12
            while len(content) <= MAX_BYTES:
                remaining = deadline - time.monotonic()
                if remaining <= 0:
                    raise ValueError()
                if connection.sock:
                    connection.sock.settimeout(remaining)
                chunk = response.read1(min(65536, MAX_BYTES + 1 - len(content)))
                if not chunk:
                    break
                content.extend(chunk)
            if len(content) > MAX_BYTES:
                raise ValueError()
            return content.decode('utf-8-sig')
        finally:
            connection.close()
    except Exception:
        raise ValueError('订阅读取失败：仅支持可公开访问的 HTTPS YAML 直链，不跟随跳转') from None


def parse_nodes(content):
    if not isinstance(content, str) or len(content.encode('utf-8')) > MAX_BYTES:
        raise ValueError('YAML 最大 1 MB')
    # Refuse aliases/tags and excessive nesting before constructing objects.
    depth = 0
    try:
        for event in yaml.parse(content):
            if isinstance(event, yaml.events.AliasEvent) or getattr(event, 'anchor', None) or getattr(event, 'tag', None):
                raise ValueError('不支持 YAML 引用或自定义标签')
            if isinstance(event, (yaml.events.MappingStartEvent, yaml.events.SequenceStartEvent)):
                depth += 1
                if depth > 12:
                    raise ValueError('YAML 嵌套过深')
            if isinstance(event, (yaml.events.MappingEndEvent, yaml.events.SequenceEndEvent)):
                depth -= 1
        data = yaml.safe_load(content)
    except yaml.YAMLError:
        raise ValueError('YAML 格式无效') from None
    nodes = data.get('proxies') if isinstance(data, dict) else None
    if not isinstance(nodes, list) or not nodes or len(nodes) > 200:
        raise ValueError('文件需包含 proxies 节点列表（最多 200 项），不支持仅含远程 provider 的文件')
    return nodes


def validate_node(node):
    if not isinstance(node, dict) or node.get('type') not in TYPES:
        raise ValueError('不支持的节点协议')
    if set(node) - FIELDS:
        raise ValueError('节点含未支持的扩展字段，请使用标准节点配置')
    if not isinstance(node.get('name'), str) or not 1 <= len(node['name']) <= 120:
        raise ValueError('节点名称无效')
    if not isinstance(node.get('server'), str) or not node['server'] or len(node['server']) > 253:
        raise ValueError('节点服务器无效')
    if type(node.get('port')) is not int or not 1 <= node['port'] <= 65535:
        raise ValueError('节点端口无效')
    for key, value in node.items():
        if key in NESTED_FIELDS:
            if not isinstance(value, dict) or set(value) - NESTED_FIELDS[key]:
                raise ValueError('节点传输选项无效')
            for subkey, subvalue in value.items():
                if subkey == 'headers':
                    if not isinstance(subvalue, dict) or len(subvalue) > 16 or any(
                            not isinstance(k, str) or not isinstance(v, str) for k, v in subvalue.items()):
                        raise ValueError('节点传输请求头无效')
                elif not isinstance(subvalue, (str, int, bool)):
                    raise ValueError('节点传输选项无效')
        elif key == 'alpn':
            if not isinstance(value, list) or any(not isinstance(v, str) for v in value):
                raise ValueError('ALPN 无效')
        elif not isinstance(value, (str, int, bool)):
            raise ValueError('节点字段无效')
    # Do not let uploaded YAML inject paths/certificate files/global listeners or rule providers.
    return dict(node)


def atomic_json(path, value):
    fd, temp = tempfile.mkstemp(dir=str(path.parent), prefix='.pending-')
    try:
        with os.fdopen(fd, 'w') as stream:
            json.dump(value, stream, ensure_ascii=False)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temp, path)
        parent_fd = os.open(str(path.parent), os.O_RDONLY)
        try:
            os.fsync(parent_fd)
        finally:
            os.close(parent_fd)
    finally:
        if os.path.exists(temp):
            os.unlink(temp)


class Manager:
    def __init__(self, directory, binary, validate_address=public_addresses):
        self.directory = Path(directory)
        self.directory.mkdir(mode=0o700, parents=True, exist_ok=True)
        self.binary = binary
        self.validate_address = validate_address
        self.registry_path = self.directory / 'registry.json'
        self.nodes = json.loads(self.registry_path.read_text()) if self.registry_path.exists() else []
        self.processes = {}

    def node(self, port):
        for node in self.nodes:
            if type(port) is int and node['port'] == port:
                return node
        raise ValueError('节点不存在')

    def listing(self):
        return [{'port': n['port'], 'name': n['name'], 'type': n['type'],
                 'running': n['port'] in self.processes and self.processes[n['port']].poll() is None}
                for n in self.nodes]

    def import_selected(self, content, indexes):
        source = parse_nodes(content)
        if (not isinstance(indexes, list) or not indexes or len(indexes) > MAX_NODES
                or any(type(i) is not int or i < 0 or i >= len(source) for i in indexes)):
            raise ValueError('请选择需导入的节点（最多 16 个）')
        staged = []
        known = {n['digest']: n for n in self.nodes}
        for index in dict.fromkeys(indexes):
            proxy = validate_node(source[index])
            addresses = self.validate_address(proxy['server'], proxy['port'])
            digest = hashlib.sha256(json.dumps(proxy, sort_keys=True).encode()).hexdigest()
            if digest in known:
                continue
            if len(self.nodes) + len(staged) >= MAX_NODES:
                raise ValueError('最多保留 16 个不可变节点，请联系运维扩容；不会覆盖现有节点')
            # Orphaned directories may outlive a registry write after a crash.
            # Never reuse their ports: an account could still be bound to them.
            port = next((p for p in range(FIRST_PORT, FIRST_PORT + MAX_NODES)
                         if not (self.directory / str(p)).exists()), None)
            if port is None:
                raise ValueError('端口容量已满，请由运维核查遗留节点；不会复用现有端口')
            directory = self.directory / str(port)
            directory.mkdir(mode=0o700)
            original_host = proxy['server']
            # Runtime never resolves a user-supplied hostname again. Preserve TLS
            # verification identity and WebSocket Host independently of dial IP.
            try:
                ipaddress.ip_address(original_host)
            except ValueError:
                if proxy['type'] in {'trojan', 'hysteria2'}:
                    proxy.setdefault('sni', original_host)
                elif proxy['type'] in {'vmess', 'vless', 'http', 'socks5'}:
                    proxy.setdefault('servername', original_host)
                if proxy.get('network') == 'ws':
                    proxy.setdefault('ws-opts', {}).setdefault('headers', {}).setdefault('Host', original_host)
            proxy['server'] = addresses[0]
            proxy['name'] = 'UPSTREAM'
            config = {'mixed-port': port, 'bind-address': '127.0.0.1', 'allow-lan': False,
                      'mode': 'rule', 'log-level': 'silent', 'ipv6': False, 'external-controller': '',
                      'dns': {'enable': False}, 'proxies': [proxy], 'rules': ['MATCH,UPSTREAM']}
            atomic_json(directory / 'config.json', config)
            result = subprocess.run([self.binary, '-t', '-d', str(directory), '-f', str(directory / 'config.json')],
                                    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=10)
            if result.returncode != 0:
                raise ValueError('节点校验失败，现有节点未变更')
            entry = {'port': port, 'name': source[index]['name'], 'type': proxy['type'],
                     'digest': digest, 'enabled': False}
            staged.append(entry)
            known[digest] = entry
        if staged:
            atomic_json(self.registry_path, self.nodes + staged)
            self.nodes += staged
        return self.listing()

    def start(self, port):
        node = self.node(port)
        if port in self.processes and self.processes[port].poll() is None:
            return
        if sum(p.poll() is None for p in self.processes.values()) >= MAX_RUNNING:
            raise ValueError('最多同时启用 8 个节点，请联系运维扩容')
        # Never inherit another service occupying the reserved listener.
        probe = socket.socket()
        try:
            probe.bind(('127.0.0.1', port))
        except OSError:
            raise ValueError('节点端口已被占用') from None
        finally:
            probe.close()
        directory = self.directory / str(port)
        process = subprocess.Popen([self.binary, '-d', str(directory), '-f', str(directory / 'config.json')],
                                   stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        self.processes[port] = process
        try:
            for _ in range(50):
                if process.poll() is not None:
                    break
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                        if not node['enabled']:
                            updated = [dict(n, enabled=True) if n['port'] == port else n for n in self.nodes]
                            atomic_json(self.registry_path, updated)
                            self.nodes = updated
                        return
                except OSError:
                    time.sleep(0.05)
            raise ValueError('节点启动失败')
        except Exception:
            process.terminate()
            process.wait(timeout=5)
            raise

    def close(self):
        for process in self.processes.values():
            if process.poll() is None:
                process.terminate()
        for process in self.processes.values():
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait()


class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass  # URLs, credentials and request bodies must never enter access logs.

    def do_POST(self):
        self.connection.settimeout(20)
        try:
            if not hmac.compare_digest(self.headers.get('Authorization', ''), 'Bearer ' + self.server.secret):
                self.reply(403, {'error': '无权限'})
                return
            length = int(self.headers.get('Content-Length', '0'))
            if length < 2 or length > MAX_BYTES * 2:
                raise ValueError('请求过大或为空')
            data = json.loads(self.rfile.read(length))
            if not isinstance(data, dict):
                raise ValueError('无效请求')
            manager = self.server.manager
            if self.path == '/list':
                result = {'nodes': manager.listing()}
            elif self.path == '/preview':
                content = fetch_subscription(data['url']) if data.get('url') else data.get('content', '')
                nodes = parse_nodes(content)
                # Stage preview server-side; browser receives names only, never credentials.
                ticket = os.urandom(24).hex()
                self.server.preview = (ticket, time.monotonic() + 300, content)
                result = {'ticket': ticket, 'nodes': [
                    {'index': i, 'name': str(n.get('name', ''))[:120], 'type': str(n.get('type', ''))[:30]}
                    for i, n in enumerate(nodes) if isinstance(n, dict)]}
            elif self.path == '/import':
                ticket, expiry, content = self.server.preview
                if not ticket or data.get('ticket') != ticket or time.monotonic() > expiry:
                    raise ValueError('预览已过期，请重新读取')
                result = {'nodes': manager.import_selected(content, data.get('indexes'))}
                self.server.preview = ('', 0, '')
            elif self.path == '/start':
                manager.start(data.get('port'))
                result = {'nodes': manager.listing()}
            else:
                raise ValueError('未知操作')
            self.reply(200, result)
        except (ValueError, KeyError, TypeError):
            # YAML/parser/network errors can embed credentials. Do not reflect exceptions.
            self.reply(400, {'error': '操作失败，请检查 YAML 格式、节点协议、数量、预览有效期及服务状态'})
        except Exception:
            self.reply(503, {'error': '节点服务暂不可用，现有账号配置未修改'})

    def reply(self, status, data):
        body = json.dumps(data, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--directory', default='/var/lib/xl-node-manager')
    parser.add_argument('--binary', default='/usr/local/bin/mihomo')
    parser.add_argument('--secret-file', required=True)
    args = parser.parse_args()
    os.umask(0o077)
    secret = Path(args.secret_file).read_text().strip()
    if len(secret) < 32:
        raise SystemExit('Manager secret must contain at least 32 characters')
    manager = Manager(args.directory, args.binary)
    server = HTTPServer(('127.0.0.1', 17990), Handler)
    server.manager, server.secret, server.preview = manager, secret, ('', 0, '')
    def stop(signum, frame):
        raise KeyboardInterrupt()
    signal.signal(signal.SIGTERM, stop)
    try:
        for node in list(manager.nodes):
            if node['enabled']:
                try:
                    manager.start(node['port'])
                except Exception:
                    pass  # Failed node stays unavailable; other nodes still start.
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()
        manager.close()


if __name__ == '__main__':
    main()
