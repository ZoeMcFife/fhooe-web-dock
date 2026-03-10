#!/bin/bash
# Root can connect without password via Unix socket during init (MYSQL_ROOT_PASSWORD is not exported when using MYSQL_RANDOM_ROOT_PASSWORD)
mariadb -u root <<-EOSQL
    GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
EOSQL