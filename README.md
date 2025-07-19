# phpelo

![logo](./logo.png)

Workarounds at the speed of sound.

The fastest way to make a simple HTTP service using PHP!

## Included:
- Router
- Ready made abstractions for
  - Set headers
  - Set content type, with one line functions for text and html
  - Set status code
  - Serve binary files in a streaming way
  - Content scopes: use echo to fill up a buffer, no need for a template library!
  - A simple markdown renderer
  - Setup CSS to embed sakuracss for out of the box nice classless styles, with automatic dark mode
  - Get information from Tailscale headers (only useful if using Tailscale/ts-proxy as reverse proxy)

## How to setup
- Have PHP
- Make a server which passes through the socket to the stdin/stdout of PHP
  - Example: ./localserver.py
  - Also can be a systemd socket activated unit: Only uses relevant resources when used!
