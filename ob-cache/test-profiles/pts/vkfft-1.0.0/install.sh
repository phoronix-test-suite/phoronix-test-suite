#!/bin/sh

tar -xf VkFFT_20200929.tar.xz

cd VkFFT_20200929/build/
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd VkFFT_20200929/build/
./Vulkan_FFT \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkfft
chmod +x vkfft
