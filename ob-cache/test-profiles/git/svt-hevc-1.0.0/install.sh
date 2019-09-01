#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

rm -rf SVT-HEVC-master
git clone https://github.com/intel/SVT-HEVC.git SVT-HEVC-master
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
