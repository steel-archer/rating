#!/bin/bash

# OWASP ZAP Baseline Scan (local)
# Requires: Docker running, app accessible at localhost:8080

set -e

TARGET="http://host.docker.internal:8080/"

echo "Checking if app is accessible..."
if ! curl -sf http://localhost:8080/ > /dev/null 2>&1; then
    echo "Error: app is not running at http://localhost:8080/"
    echo "Run: docker compose up -d"
    exit 1
fi

echo "Starting ZAP Baseline Scan..."

WRK_DIR=$(mktemp -d)
cp "$(pwd)/.github/zap-rules.tsv" "$WRK_DIR/rules.tsv"

docker run --rm \
    --add-host=host.docker.internal:host-gateway \
    -v "$WRK_DIR:/zap/wrk" \
    ghcr.io/zaproxy/zaproxy:stable \
    zap-baseline.py \
    -t "$TARGET" \
    -c rules.tsv \
    -d \
    -I

rm -rf "$WRK_DIR"

echo "Done."
