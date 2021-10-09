#!/bin/sh

tar -xf ffmpeg-4.4.tar.bz2

echo "#!/bin/sh
cd ffmpeg-4.4
if [ \$OS_TYPE = \"BSD\" ]
then
	gmake -s -j \$NUM_CPU_CORES 2>&1
else
        make -s -j \$NUM_CPU_CORES 2>&1
fi
echo \$? > ~/test-exit-status" > build-ffmpeg

chmod +x build-ffmpeg
