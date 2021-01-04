#!/bin/sh

unzip -o Vulkan_FFT_release_v1.1.1.zip
chmod +x Vulkan_FFT.exe

echo "#!/bin/sh
./Vulkan_FFT.exe \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkfft
chmod +x vkfft
