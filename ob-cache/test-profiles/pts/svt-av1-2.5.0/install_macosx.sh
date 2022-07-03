#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z  -aoa
rm -f Bosphorus_Copyright.txt
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z  -aoa

unzip -o SVT-AV1-v1.0.0-macos.zip
chmod +x Bin/Release/SvtAv1EncApp

echo "#!/bin/sh
./Bin/Release/SvtAv1EncApp \$@ > \$LOG_FILE 2>&1" > svt-av1
chmod +x svt-av1
