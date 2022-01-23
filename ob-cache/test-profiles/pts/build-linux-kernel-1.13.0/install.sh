#!/bin/sh

echo "#!/bin/sh

cd linux-5.16
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > time-compile-kernel

chmod +x time-compile-kernel
