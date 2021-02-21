#!/bin/sh

tar -xf VkResample-1.0.0.tar.gz

cd VkResample-1.0.0
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd VkResample-1.0.0/build/
./VkResample -i ~/VkResample-1.0.0/samples/native_4k.png \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkresample
chmod +x vkresample
