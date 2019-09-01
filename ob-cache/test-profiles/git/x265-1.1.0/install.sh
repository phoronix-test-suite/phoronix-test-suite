#!/bin/sh

git clone https://github.com/videolan/x265 x265-master
cd x265-master/build
cmake ../source
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

echo "#!/bin/sh
./x265-master/build/x265 Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x265
chmod +x x265
