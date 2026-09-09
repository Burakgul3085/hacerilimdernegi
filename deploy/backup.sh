#!/usr/bin/env bash
set -euo pipefail

STAMP=$(date +%Y%m%d_%H%M%S)
DEST=/var/backups/hacerilim
mkdir -p "$DEST"

mysqldump -u hacerilim -p'CHANGE_ME_STRONG_PASSWORD' hacerilim | gzip > "$DEST/db_${STAMP}.sql.gz"
tar -czf "$DEST/storage_${STAMP}.tgz" -C /var/www/hacerilim storage/app

find "$DEST" -type f -mtime +21 -delete
