#!/bin/sh

tar -xjf sample_html_page-1.tar.bz2

echo "#!/bin/sh

\$SYSTEM_MONITOR_START system.battery-discharge-rate all-comma 1
sleep 60
xset dpms force off
sleep 60
xset dpms force on
sleep 5
glxgears
\$TIMED_KILL glxgears 60
sleep 5
xdg-open sample_html_page/index.html &
sleep 60
\$SYSTEM_MONITOR_STOP \$LOG_FILE" > battery-power-usage
chmod +x battery-power-usage
