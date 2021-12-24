#!/bin/sh

unzip -o Kvazaar-2.1.0-win64-release.zip
chmod +x kvazaar.exe
mv kvazaar.exe k.exe

7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa

echo "#!/bin/sh
./k.exe --threads \$NUM_CPU_CORES \$@ -o out.hevc > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -f out.hevc" > kvazaar
chmod +x kvazaar
