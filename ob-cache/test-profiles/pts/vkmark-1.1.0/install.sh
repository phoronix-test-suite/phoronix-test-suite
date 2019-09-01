#!/bin/sh

tar -xf vkmark-20180530.tar.xz

cd vkmark-20180530/
git submodule update --init
mkdir build
meson build
ninja -C build
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd vkmark-20180530
./build/src/vkmark \$@ > \$LOG_FILE" > vkmark-run
chmod +x vkmark-run
