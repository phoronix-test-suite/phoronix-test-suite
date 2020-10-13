#!/bin/sh

unzip -o realsr-ncnn-vulkan-20200818-windows.zip

cd realsr-ncnn-vulkan-20200818-windows/
tar -xf ../lowend-image-samples-1.tar.xz

cd ~/
cat>realsr-ncnn<<EOT
#!/bin/sh
cd realsr-ncnn-vulkan-20200818-windows/
./realsr-ncnn-vulkan.exe -i low-end-image-sample1.JPG -o out.png \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x realsr-ncnn
