#!/bin/sh

echo "#!/bin/sh

\$SYSTEM_MONITOR_START cpu.usage all-comma 1
sleep 5
for i in 1 2 3
do
   \$TEST_MPLAYER_BASE/mplayer \$@ \$TEST_VIDEO_SAMPLE/Grey.ts
done
sleep 5
\$SYSTEM_MONITOR_STOP \$LOG_FILE" > video-cpu-usage
chmod +x video-cpu-usage
