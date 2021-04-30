#!/bin/sh

echo "#!/bin/sh

cd build
cmake --build . -- -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-llvm

chmod +x build-llvm
