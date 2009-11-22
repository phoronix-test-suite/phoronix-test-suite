#!/bin/sh

tar -xjf sample_html_page-1.tar.bz2

echo "#!/bin/sh

\$SYSTEM_MONITOR_START system.battery-discharge-rate all-comma 1
sleep 60
xset dpms force off
sleep 60
xset dpms force on
sleep 5
\$TEST_MPLAYER_BASE/mplayer -vo xv -fs \$TEST_VIDEO_SAMPLE/Grey.ts
\$SYSTEM_MONITOR_STOP \$LOG_FILE" > battery-power-usage
chmod +x battery-power-usage
