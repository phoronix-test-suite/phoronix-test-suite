#!/bin/sh

echo "#!/bin/sh
sleep 5
\$TEST_MPLAYER_BASE/mplayer \$@ big_buck_bunny_1080p_h264.mov
echo \$? > ~/test-exit-status
sleep 5" > video-cpu-usage
chmod +x video-cpu-usage
