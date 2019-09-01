#!/bin/sh
kill -TERM `cat $HOME/Apache24/logs/httpd.pid`
sleep 10
rm -f $HOME/Apache24/logs/*
