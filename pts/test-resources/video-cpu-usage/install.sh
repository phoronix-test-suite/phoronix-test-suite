#!/bin/sh

echo "#!/bin/sh
sleep 10
\$TEST_MPLAYER_BASE/mplayer \$@ big_buck_bunny_1080p_h264.mov
sleep 10" > video-cpu-usage
chmod +x video-cpu-usage
