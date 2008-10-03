#!/bin/sh

tar -xjf ffmpeg-may27-2008.tar.bz2

cd ffmpeg-may27-2008/
./configure > /dev/null
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh

\$TIMER_START
./ffmpeg-may27-2008/ffmpeg -i \$TEST_EXTENDS/pts-trondheim.avi -y -target ntsc-vcd /dev/null 2>&1
\$TIMER_STOP" > ffmpeg
chmod +x ffmpeg
