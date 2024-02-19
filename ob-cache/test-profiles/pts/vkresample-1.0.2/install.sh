#!/bin/sh
tar -xf VkResample-1.0.2.tar.gz
cd VkResample-1.0.2
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd VkResample-1.0.2/build/
./VkResample -i ~/VkResample-1.0.2/samples/native_4k.png \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkresample
chmod +x vkresample
