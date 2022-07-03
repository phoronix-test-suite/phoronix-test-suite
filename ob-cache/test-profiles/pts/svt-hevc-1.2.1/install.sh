#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa

tar -xf SVT-HEVC-1.5.0.tar.gz
cd SVT-HEVC-1.5.0
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./SVT-HEVC-1.5.0/Bin/Release/SvtHevcEncApp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-hevc
chmod +x svt-hevc
