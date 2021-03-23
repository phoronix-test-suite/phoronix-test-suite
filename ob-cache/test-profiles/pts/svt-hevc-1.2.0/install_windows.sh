#!/bin/sh
# Windows support currently dropped for lack of updated binaries.
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z
unzip -o SVT-HEVC-1.4.1-Windows.zip

echo "#!/bin/sh
./SvtHevcEncApp.exe \$@ > \$LOG_FILE 2>&1" > svt-hevc
chmod +x svt-hevc
