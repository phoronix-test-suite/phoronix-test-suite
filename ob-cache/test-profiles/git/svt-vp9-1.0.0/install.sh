#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

rm -rf SVT-VP9-master
git clone https://github.com/OpenVisualCloud/SVT-VP9.git SVT-VP9-master
cd SVT-VP9-master/Build/linux
chmod +x build.sh
./build.sh release
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./SVT-VP9-master/Bin/Release/SvtVp9EncApp -i Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv -w 1920 -h 1080 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-vp9
chmod +x svt-vp9
