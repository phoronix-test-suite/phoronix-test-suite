#!/bin/sh
echo "#!/bin/sh
cd linux-6.8
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-linux-kernel
chmod +x build-linux-kernel
