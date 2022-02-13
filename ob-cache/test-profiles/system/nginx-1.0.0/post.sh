#!/bin/sh
nginx -c $HOME/nginx.conf -s quit
killall nginx
rm -f nginx_/logs/*
sleep 3
