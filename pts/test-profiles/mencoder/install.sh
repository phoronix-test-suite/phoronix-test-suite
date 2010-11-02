#!/bin/sh

echo "#!/bin/sh
\$TEST_MPLAYER_BASE/mencoder \$TEST_VIDEO_SAMPLE/pts-trondheim.avi -o /dev/null -ovc lavc -oac copy -lavcopts vcodec=mpeg4:threads=\$NUM_CPU_CORES:mbd=2:trell=1:v4mv=1:vstrict=1
echo \$? > ~/test-exit-status" > mencoder
chmod +x mencoder
