#!/bin/sh
unzip -o VkFFT_Vulkan_v1.3.4.zip
chmod +x VkFFT_TestSuite.exe
echo "#!/bin/sh
./VkFFT_TestSuite.exe \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkfft
chmod +x vkfft
