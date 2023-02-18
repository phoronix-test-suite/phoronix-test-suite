#!/bin/sh

tar -xf vkmark-20220516.tar.xz

cd vkmark-20220516/
mkdir build
meson -Dkms=false build
# Limit threads as easily hitting OOM issues on Ubuntu 22.04...
ninja -C build -j 4
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd vkmark-20220516
./build/src/vkmark \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > vkmark-run
chmod +x vkmark-run
