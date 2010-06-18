#!/bin/sh

echo "#!/bin/sh

\$SYSTEM_MONITOR_START cpu.usage all-comma 1
sleep 10
\$TEST_MPLAYER_BASE/mplayer \$@ big_buck_bunny_1080p_h264.mov
sleep 10
\$SYSTEM_MONITOR_STOP \$LOG_FILE" > video-cpu-usage
chmod +x video-cpu-usage
