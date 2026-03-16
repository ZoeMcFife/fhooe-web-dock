#!/bin/bash
# Root password is required: init scripts run after the entrypoint has set it (no socket auth in this image).
mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<-EOSQL
    GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
EOSQL