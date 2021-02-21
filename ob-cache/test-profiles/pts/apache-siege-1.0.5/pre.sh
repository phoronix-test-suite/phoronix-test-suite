#!/bin/sh
./httpd_/bin/apachectl -k start -f $HOME/httpd_/conf/httpd.conf
sleep 5

cd siege-3.1.4/utils
bash siege.config
cd ~
