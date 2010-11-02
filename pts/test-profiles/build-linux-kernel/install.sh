#!/bin/sh

tar -zxvf linux-2625-config.tar.gz

echo "#!/bin/sh

cd linux-2.6.25/
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status" > time-compile-kernel

chmod +x time-compile-kernel
