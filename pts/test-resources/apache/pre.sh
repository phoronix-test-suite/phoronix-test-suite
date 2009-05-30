#!/bin/sh
./httpd_/bin/apachectl -k start -f $(pwd)/httpd_/conf/httpd.conf
sleep 5
