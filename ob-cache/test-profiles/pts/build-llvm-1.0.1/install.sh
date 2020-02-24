#!/bin/sh

echo "#!/bin/sh

cd build
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status" > build-llvm

chmod +x build-llvm
