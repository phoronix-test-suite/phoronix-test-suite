#!/bin/sh

tar -xf libwebp2-20210126.tar.xz
unzip -o sample-photo-6000x4000-1.zip


cd libwebp2-master
mkdir build
cd build
cmake .. -DWP2_ENABLE_SIMD=ON -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./libwebp2-master/build/cwp2 sample-photo-6000x4000.JPG -o out.webp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > webp2
chmod +x webp2
