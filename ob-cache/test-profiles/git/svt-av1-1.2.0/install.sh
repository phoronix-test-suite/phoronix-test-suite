#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

rm -rf SVT-AV1-master
git clone https://github.com/OpenVisualCloud/SVT-AV1.git SVT-AV1-master
cd SVT-AV1-master/Build/linux
./build.sh release
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./SVT-AV1-master/Bin/Release/SvtAv1EncApp -i Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv -w 1920 -h 1080 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-av1
chmod +x svt-av1
