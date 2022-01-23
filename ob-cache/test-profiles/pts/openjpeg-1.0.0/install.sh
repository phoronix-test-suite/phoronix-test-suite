#!/bin/sh

tar -xf openjpeg-2.4.0.tar.gz
cd openjpeg-2.4.0
mkdir build
cd build
cmake .. -DCMAKE_BUILD_TYPE=Release
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
fi

cd ~
echo "#!/bin/sh
./openjpeg-2.4.0/build/bin/opj_compress -threads \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -f out.jp2" > openjpeg
chmod +x openjpeg
