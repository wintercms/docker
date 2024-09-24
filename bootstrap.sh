#!/bin/sh

# This script is used to bootstrap Winter
if [ -e /bootstrap/bootstrap.php ]; then
    php bootstrap.php
fi

# Remove bootstrap afterwards
rm -rf /bootstrap/*

# Run supervisord
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
