#!/bin/sh

test_directory=$HOME/html
httpdconf=${test_directory}/httpd.conf
if [ ! -d /run/httpd ]; then
	mkdir -p /run/httpd
fi
apachectl stop
if [ -f /usr/sbin/httpd ]; then
	httpd -k start -f ${httpdconf}
else
	apache2 -k start -f ${httpdconf}
fi
sleep 5
