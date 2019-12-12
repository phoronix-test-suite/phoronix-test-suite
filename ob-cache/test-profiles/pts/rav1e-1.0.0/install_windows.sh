#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

echo "#!/bin/sh
./rav1e-20191023.exe Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m --threads \$NUM_CPU_CORES --tile-rows 4 --tile-cols 4 --output output --limit 60 > log.out 2>&1
rm -f output

tr -s '\r' '\n' < log.out > \$LOG_FILE" > rav1e
chmod +x rav1e
