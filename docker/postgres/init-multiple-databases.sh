#!/usr/bin/env sh
set -eu

if [ -z "${POSTGRES_MULTIPLE_DATABASES:-}" ]; then
  exit 0
fi

for db in $(echo "$POSTGRES_MULTIPLE_DATABASES" | tr ',' ' '); do
  echo "Creating database '$db'"
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE "$db";
    GRANT ALL PRIVILEGES ON DATABASE "$db" TO "$POSTGRES_USER";
EOSQL
done
