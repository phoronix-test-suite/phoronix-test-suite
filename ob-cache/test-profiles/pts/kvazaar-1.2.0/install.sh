#!/bin/sh
tar -xf kvazaar-2.2.0.tar.bz2
cd kvazaar-2.2.0/
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa
echo "#!/bin/sh
./kvazaar-2.2.0/src/kvazaar --threads \$NUM_CPU_CORES \$@ -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > kvazaar
chmod +x kvazaar
