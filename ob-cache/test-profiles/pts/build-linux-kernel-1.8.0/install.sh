#!/bin/sh

echo "#!/bin/sh

cd linux-4.13/
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status" > time-compile-kernel

chmod +x time-compile-kernel
