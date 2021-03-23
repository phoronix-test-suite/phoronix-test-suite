#!/bin/sh

tar -xf jpeg-xl-v0.3.3.tar.gz
tar -xf png-samples-1.tar.xz
unzip -o sample-photo-6000x4000-1.zip

cd jpeg-xl-v0.3.3/
./deps.sh
mkdir build
cd build
cmake .. -DCMAKE_BUILD_TYPE=Release -DBUILD_TESTING=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./jpeg-xl-v0.3.3/build/tools/cjxl \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > jpegxl
chmod +x jpegxl
