#!/bin/sh
./nginx_/sbin/nginx -s quit
rm -f nginx_/logs/*
sleep 3
