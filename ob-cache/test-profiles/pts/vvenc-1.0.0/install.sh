#!/bin/sh
tar -xf vvenc-1.7.0.tar.gz
cd vvenc-1.7.0
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release ..
cmake --build . -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa
echo "#!/bin/sh
./vvenc-1.7.0/bin/release-static/vvencapp --threads \$NUM_CPU_CORES \$@ -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > vvenc
chmod +x vvenc
