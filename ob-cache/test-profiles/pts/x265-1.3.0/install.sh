#!/bin/sh

tar -xf x265_3.4.tar.gz
cd x265_3.4/build
cmake ../source
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa

echo "#!/bin/sh
./x265_3.4/build/x265 \$@ /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x265
chmod +x x265
