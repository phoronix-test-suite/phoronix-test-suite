#!/bin/sh

unzip -o kvazaar-2.0.0-Win64-Release.zip
chmod +x kvazaar.exe

7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa

echo "#!/bin/sh
./kvazaar.exe --threads \$NUM_CPU_CORES \$@ -o out.hevc > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -f out.hevc" > kvazaar
chmod +x kvazaar
