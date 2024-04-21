#!/bin/sh
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
tar -xf SVT-AV1-v2.0.0.tar.bz2
cd SVT-AV1-v2.0.0/Build/linux
EXTRA_FLAGS="--enable-lto "
if [ $OS_TYPE = "Linux" ]
then
    if grep avx512 /proc/cpuinfo > /dev/null
    then
	EXTRA_FLAGS="$EXTRA_FLAGS --enable-avx512"
    fi
fi
./build.sh release $EXTRA_FLAGS
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
./SVT-AV1-v2.0.0/Bin/Release/SvtAv1EncApp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > svt-av1
chmod +x svt-av1
