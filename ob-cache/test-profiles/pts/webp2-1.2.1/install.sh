#!/bin/sh
tar -xf libwebp2-20220823.tar.xz
unzip -o sample-photo-6000x4000-1.zip
cd libwebp2-master
mkdir build
cd build
cmake .. -DWP2_ENABLE_SIMD=ON -DCMAKE_BUILD_TYPE=Release -DWP2_BUILD_TESTS=OFF -DWP2_ENABLE_TESTS=OFF
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
./libwebp2-master/build/cwp2 sample-photo-6000x4000.JPG -o out.webp  -mt \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
rm -f out.webp
echo \$? > ~/test-exit-status" > webp2
chmod +x webp2
