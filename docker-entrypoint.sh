#!/bin/bash

set -ex

POSTFIXADMIN_DB_TYPE=mysqli
POSTFIXADMIN_DB_HOST=$POSTFIX_DB_HOST
POSTFIXADMIN_DB_NAME=$POSTFIX_DB_NAME
POSTFIXADMIN_DB_USER=$POSTFIX_DB_USER

set +x -v
POSTFIXADMIN_DB_PASSWORD=$POSTFIX_DB_PASSWORD
set -x +v

if [ ! -e config.local.php ]; then
    touch config.local.php
    echo "Write config to $PWD/config.local.php"
    set +x -v
    echo "<?php
    \$CONF['database_type'] = '${POSTFIXADMIN_DB_TYPE}';
    \$CONF['database_host'] = '${POSTFIXADMIN_DB_HOST}';
    \$CONF['database_user'] = '${POSTFIXADMIN_DB_USER}';
    \$CONF['database_password'] = '${POSTFIXADMIN_DB_PASSWORD}';
    \$CONF['database_name'] = '${POSTFIXADMIN_DB_NAME}';
    \$CONF['setup_password'] = '${POSTFIXADMIN_SETUP_PASSWORD}';
    \$CONF['configured'] = true;
    ?>" > config.local.php
    set -x +v
else
    echo "WARNING: $PWD/config.local.php already exists."
    echo "Postfixadmin related environment variables have been ignored."
fi

php create-postfixadmin-db.php

if [ -f upgrade.php ]; then
    echo " ** Running database / environment upgrade.php "
    php upgrade.php
fi

if ! grep -q "ServerName $SERVER_NAME" /etc/apache2/apache2.conf; then
    echo ServerName $SERVER_NAME >> /etc/apache2/apache2.conf
fi

exec "$@"