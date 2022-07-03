#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa

chmod +x x264-r3094-bfc87b7.exe

echo "#!/bin/sh
./x264-r3094-bfc87b7.exe -o output \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

rm -f output" > x264
chmod +x x264
