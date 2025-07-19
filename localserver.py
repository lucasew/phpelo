#!/usr/bin/env python3

from pathlib import Path
from argparse import ArgumentParser
import socket
import logging
import subprocess
import shutil
import os
import sys

logger = logging.getLogger('phpelo_server')

def main():
    parser = ArgumentParser()
    parser.add_argument("script_dir", type=Path)
    parser.add_argument('--port', '-p', default=8080, type=int)
    parser.add_argument('-v', '--verbose', action='store_true')
    parser.add_argument('-i', '--php', default=shutil.which('php'), type=Path)
    args = parser.parse_args()
    os.environ['SCRIPT_DIR'] = str(args.script_dir.resolve())
    assert args.php is not None and args.php.exists(), 'php not found'

    logging.basicConfig(level=logging.DEBUG if args.verbose else logging.INFO)

    sock = socket.create_server(("127.0.0.1", args.port))
    logger.info(f'Starting server on 127.0.0.1:{args.port}')
    sock.listen()
    while True:
        conn, addr = sock.accept()
        subprocess.run([
          'php',
          "-d", "display_errors=\"stderr\"",
          "-d", "disable_functions=\"header\"",
          Path(__file__).parent / "entrypoint.php"
      ], stdin=conn.fileno(), stdout=conn.fileno(), stderr=sys.stderr)
        conn.close()

if __name__ == '__main__':
    main()
