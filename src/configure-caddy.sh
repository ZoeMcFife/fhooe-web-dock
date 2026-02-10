#!/bin/bash
# Writes the fhooe-web-dock Caddyfile into the image (no volume, no file in repo).
# Multi-project layout: each project has /public/index.php; replaces distributed .htaccess.

set -e

CADDYFILE="/etc/frankenphp/Caddyfile"

echo "## Configuring FrankenPHP Caddyfile ##"
cat << 'EOF' > "$CADDYFILE"
# FrankenPHP Caddyfile for fhooe-web-dock
# Multi-project layout (teaching): each project has /public/index.php (fhooe-router, Slim, etc.).
# Replaces distributed .htaccess with a single "public island" routing logic.
# See https://frankenphp.dev/docs/config | https://caddyserver.com/docs/caddyfile

{
    skip_install_trust
    frankenphp {
    }
}

:80 {
    # Root = Docker volume ./webapp:/app/public
    root * /app/public
    encode zstd br gzip

    # 1. MULTI-APP: paths containing "/public" -> serve index.php from that same public folder
    # Group 1 (re.app.1): path up to /public (e.g. /hyp2ue-t1-examples/ue11/public)
    # Group 2 (re.app.2): remainder (virtual route, e.g. /api/users or /blog/en)
    @public_apps path_regexp app ^(/.*/public)(/.*)?$

    handle @public_apps {
        # a) Physical file exists? (e.g. …/public/css/style.css) -> serve it
        # b) Otherwise: pass request to index.php in the same public folder
        try_files {path} {path}/ {re.app.1}/index.php
        php_server
    }

    # 2. SPECIAL CASE: directories WITHOUT index.php (e.g. project root folders)
    # file {path}/ -> path is an existing directory
    # not file {path}/index.php -> no index.php in that directory
    @no_index_file {
        file {path}/
        not file {path}/index.php
    }

    # For those: directory listing only, no php_server greedy fallback
    handle @no_index_file {
        file_server browse
    }

    # 3. DEFAULT: everything else
    # php_server only where PHP should run; otherwise serve static files.
    handle {
        php_server
        file_server
    }
}
EOF
echo "Caddyfile written to $CADDYFILE"
