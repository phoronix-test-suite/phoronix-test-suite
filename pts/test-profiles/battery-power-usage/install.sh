#!/bin/sh

echo "#!/bin/sh
sleep 60
xset dpms force off
sleep 60
xset dpms force on
sleep 5
\$TEST_MPLAYER_BASE/mplayer -vo xv -fs \$TEST_VIDEO_SAMPLE/Grey.ts" > battery-power-usage
chmod +x battery-power-usage
