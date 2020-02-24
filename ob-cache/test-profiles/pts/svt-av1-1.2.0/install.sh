#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

unzip -o SVT-AV1-20190307.zip
cd SVT-AV1-master/Build/linux
./build.sh release
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./SVT-AV1-master/Bin/Release/SvtAv1EncApp -i Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv -w 1920 -h 1080 -n 200 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-av1
chmod +x svt-av1
