#!/bin/sh
tar -xf vkmark-20220516.tar.xz
cd vkmark-20220516/
cp src/scene.h src/scene.h.orig
echo "#include <cstdint>
#include <memory>" > src/scene.h
cat src/scene.h.orig >> src/scene.h
cp src/ws/swapchain_window_system.h src/ws/swapchain_window_system.h.orig
echo "#include <cstdint>
#include <memory>" > src/ws/swapchain_window_system.h
cat src/ws/swapchain_window_system.h.orig >> src/ws/swapchain_window_system.h
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
