#!/bin/sh

tar -xf llvm-6.0.0.src.tar.xz
tar -xf test-suite-6.0.0.src.tar.xz

cd llvm-6.0.0.src/utils/lit/
python3 setup.py build

cd ~
mkdir test-suite-build
cd test-suite-build
cmake ../test-suite-6.0.0.src -DTEST_SUITE_COLLECT_CODE_SIZE=Off
make -j $NUM_CPU_CORES

cd ~

echo "#!/bin/sh
cd test-suite-build
../llvm-6.0.0.src/utils/lit/lit.py -v -j 1 . -o results.json > \$LOG_FILE 2>&1
" > llvm-test-suite

chmod +x llvm-test-suite
