#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa

tar -xf SVT-AV1-v0.9.0.tar.bz2
cd SVT-AV1-v0.9.0/Build/linux
#export CFLAGS="-O3 -fcommon"
#export CXXFLAGS="-O3 -fcommon"
./build.sh release
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./SVT-AV1-v0.9.0/Bin/Release/SvtAv1EncApp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-av1
chmod +x svt-av1
