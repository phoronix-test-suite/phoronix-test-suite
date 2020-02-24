#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

echo "#!/bin/sh
./x264-r2969-d4099dd.exe -o output --slow --threads \$NUM_CPU_CORES Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x264
chmod +x x264
