#!/bin/sh
./httpd_/bin/apachectl -k stop
rm -f httpd_/logs/*
sleep 3
