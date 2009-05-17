#!/bin/sh

tar -xjf ffmpeg-0.5.tar.bz2

cd ffmpeg-0.5/
./configure > /dev/null
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh

\$TIMER_START
./ffmpeg-0.5/ffmpeg -i \$TEST_EXTENDS/pts-trondheim.avi -threads \$NUM_CPU_CORES -y -target ntsc-vcd /dev/null 2>&1
\$TIMER_STOP" > ffmpeg
chmod +x ffmpeg
