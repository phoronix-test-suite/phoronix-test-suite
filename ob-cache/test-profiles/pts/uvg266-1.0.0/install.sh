#!/bin/sh
tar -xf uvg266-0.4.1.tar.gz
cd uvg266-0.4.1/build/
cmake -DCMAKE_BUILD_TYPE=Release ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa
echo "#!/bin/sh
./uvg266-0.4.1/build/uvg266 --threads \$NUM_CPU_CORES \$@ -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > uvg266
chmod +x uvg266
