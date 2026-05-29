#!/bin/bash
# -----------------------------------------------------------------------------
# Generate a self-signed SSL certificate for https://<PROJECT_NAME>/.
# PROJECT_NAME is read from the repo's .env file (or .env.example as fallback,
# or finally from the parent directory name), so this script is reusable across
# projects without any local edits.
# -----------------------------------------------------------------------------
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SSL_DIR="$SCRIPT_DIR/apache/ssl"

# --- Resolve PROJECT_NAME ---------------------------------------------------
# Search order: new layout (.env next to this script) -> legacy layout
# (.env at the project root) -> fallback to parent folder name.
PROJECT_NAME=""
for envfile in "$SCRIPT_DIR/.env" "$SCRIPT_DIR/.env.example" "$ROOT_DIR/.env" "$ROOT_DIR/.env.example"; do
    if [ -f "$envfile" ]; then
        # shellcheck disable=SC1090
        PROJECT_NAME="$(grep -E '^PROJECT_NAME=' "$envfile" | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" )"
        if [ -n "$PROJECT_NAME" ]; then
            echo "[info] PROJECT_NAME=$PROJECT_NAME (from $(basename "$envfile"))"
            break
        fi
    fi
done
if [ -z "$PROJECT_NAME" ]; then
    PROJECT_NAME="$(basename "$ROOT_DIR")"
    echo "[info] PROJECT_NAME=$PROJECT_NAME (fallback to folder name)"
fi

mkdir -p "$SSL_DIR"

if [ -f "$SSL_DIR/$PROJECT_NAME.crt" ] && [ -f "$SSL_DIR/$PROJECT_NAME.key" ]; then
    echo "[ok] Certificate already exists at $SSL_DIR/$PROJECT_NAME.{crt,key}"
    exit 0
fi

echo "[info] Generating self-signed certificate for $PROJECT_NAME..."

CONF="$(mktemp)"
cat > "$CONF" <<EOF
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req

[dn]
CN=$PROJECT_NAME
O=Development
C=RO

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = $PROJECT_NAME
DNS.2 = www.$PROJECT_NAME
DNS.3 = localhost
IP.1  = 127.0.0.1
EOF

openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -config "$CONF" \
    -extensions v3_req \
    -keyout "$SSL_DIR/$PROJECT_NAME.key" \
    -out    "$SSL_DIR/$PROJECT_NAME.crt"

rm -f "$CONF"

echo "[ok] Certificate generated:"
echo "     $SSL_DIR/$PROJECT_NAME.crt"
echo "     $SSL_DIR/$PROJECT_NAME.key"
echo
echo "[next] Add this line to your hosts file (requires admin/sudo):"
echo "       127.0.0.1  $PROJECT_NAME"
echo
echo "       Windows: C:\\Windows\\System32\\drivers\\etc\\hosts"
echo "       Linux/macOS: /etc/hosts"
