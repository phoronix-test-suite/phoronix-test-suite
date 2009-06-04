#!/bin/sh
rm -f $HOME/pts-system-monitoring-to-kill
sleep 6
cat $HOME/pts-system-monitoring-results > $1
rm -f $HOME/pts-system-monitoring-results

