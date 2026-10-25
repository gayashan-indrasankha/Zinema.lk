"""Fixed TCP gateway: only loopback-published ports to the internal web namespace.
No dynamic upstream, CONNECT, credentials, app source or request logging.
"""
import select
import socket
import socketserver
import threading


class Relay(socketserver.BaseRequestHandler):
    def handle(self):
        with socket.create_connection(('web', self.server.server_address[1]), timeout=5) as upstream:
            peers = {self.request: upstream, upstream: self.request}
            while True:
                ready, _, _ = select.select(list(peers), [], [], 60)
                if not ready: return
                for peer in ready:
                    data = peer.recv(65536)
                    if not data: return
                    peers[peer].sendall(data)


class Server(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True
    def handle_error(self, *_): pass


for port in [8088, 9081]:
    server = Server(('0.0.0.0', port), Relay)
    threading.Thread(target=server.serve_forever, daemon=False).start()
