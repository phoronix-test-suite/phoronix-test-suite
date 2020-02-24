#!/bin/sh


tar -xf ffmpeg-4.2.2.tar.bz2

echo "#!/bin/sh
cd ffmpeg-4.2.2
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-ffmpeg

chmod +x build-ffmpeg
