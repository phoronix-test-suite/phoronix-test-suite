#!/bin/sh

unzip -o waifu2x-ncnn-vulkan-20200818-windows.zip

cd waifu2x-ncnn-vulkan-20200818-windows/
tar -xf ../lowend-image-samples-1.tar.xz

cd ~/
cat>waifu2x-ncnn<<EOT
#!/bin/sh
cd waifu2x-ncnn-vulkan-20200818-windows/
./waifu2x-ncnn-vulkan.exe -i low-end-image-sample1.JPG -o out.png \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x waifu2x-ncnn
