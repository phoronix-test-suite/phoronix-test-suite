#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

unzip -o SVT-VP9-0.1.0.zip
cd SVT-VP9-0.1.0/Build/linux
export CFLAGS="-O3 -fcommon"
export CXXFLAGS="-O3 -fcommon"
./build.sh release
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./SVT-VP9-0.1.0/Bin/Release/SvtVp9EncApp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-vp9
chmod +x svt-vp9
