#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

unzip -o SVT-HEVC-20190203.zip
cd SVT-HEVC-master/
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./SVT-HEVC-master/Bin/Release/SvtHevcEncApp -i Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv -w 1920 -h 1080 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-hevc
chmod +x svt-hevc
