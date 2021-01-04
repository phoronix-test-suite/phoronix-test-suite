#!/bin/sh

tar -xf vkmark-20200521.tar.xz

cd vkmark-20200521/
mkdir build
meson build
ninja -C build
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd vkmark-20200521
./build/src/vkmark \$@ > \$LOG_FILE" > vkmark-run
chmod +x vkmark-run
