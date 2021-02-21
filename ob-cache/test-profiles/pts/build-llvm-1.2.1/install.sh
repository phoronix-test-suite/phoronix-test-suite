#!/bin/sh

echo "#!/bin/sh

cd build
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-llvm

chmod +x build-llvm
