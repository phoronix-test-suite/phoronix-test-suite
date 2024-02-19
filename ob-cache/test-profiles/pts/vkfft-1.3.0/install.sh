#!/bin/sh
tar -xf VkFFT-1.3.4.tar.gz
cd VkFFT-1.3.4
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release -DALLOW_EXTERNAL_SPIRV_TOOLS=TRUE ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd VkFFT-1.3.4/build/
./VkFFT_TestSuite \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkfft
chmod +x vkfft
