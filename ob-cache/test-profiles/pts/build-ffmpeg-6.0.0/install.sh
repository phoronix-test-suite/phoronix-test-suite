#!/bin/sh
tar -xf ffmpeg-6.0.tar.xz
echo "#!/bin/sh
cd ffmpeg-6.0
if [ \$OS_TYPE = \"BSD\" ]
then
	gmake -s -j \$NUM_CPU_CORES 2>&1
else
        make -s -j \$NUM_CPU_CORES 2>&1
fi
echo \$? > ~/test-exit-status" > build-ffmpeg
chmod +x build-ffmpeg
