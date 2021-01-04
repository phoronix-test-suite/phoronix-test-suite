#!/bin/sh

tar -xf VkFFT-1.1.1.tar.gz

cd VkFFT-1.1.1
mkdir build
cd build

cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd VkFFT-1.1.1/build/
./Vulkan_FFT \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkfft
chmod +x vkfft
