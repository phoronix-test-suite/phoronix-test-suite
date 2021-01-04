#!/bin/sh

chmod +x VkResample-100.exe
tar -xf VkResample-1.0.0.tar.gz

echo "#!/bin/sh
./VkResample-100.exe -i VkResample-1.0.0/samples/native_4k.png \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkresample
chmod +x vkresample
