#!/bin/sh

tar -xjf ffmpeg-0.5.tar.bz2

cd ffmpeg-0.5/
./configure
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh

\$TIMER_START
./ffmpeg-0.5/ffmpeg -i \$TEST_EXTENDS/pts-trondheim.avi -threads \$NUM_CPU_CORES -y -target ntsc-vcd /dev/null 2>&1
echo \$? > ~/test-exit-status
\$TIMER_STOP" > ffmpeg
chmod +x ffmpeg
