#!/bin/sh

test_directory=$HOME/html
httpdconf=${test_directory}/httpd.conf
if [ -f /usr/sbin/httpd ]; then
	httpd -k stop -f ${httpdconf}
else
	apache2 -k stop -f ${httpdconf}
fi
sleep 5
apachectl start
