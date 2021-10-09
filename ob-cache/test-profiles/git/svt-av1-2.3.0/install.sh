#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa

rm -rf SVT-AV1-master
git clone https://gitlab.com/AOMediaCodec/SVT-AV1.git SVT-AV1-master
./SVT-AV1-master/Build/linux/build.sh release
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./SVT-AV1-master/Bin/Release/SvtAv1EncApp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-av1
chmod +x svt-av1

