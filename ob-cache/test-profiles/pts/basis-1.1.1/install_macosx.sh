#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf basis_universal-1.12.tar.gz
cd basis_universal-1.12/
cmake CMakeLists.txt
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd basis_universal-1.12/bin_osx
./basisu \$@ ~/sample-*.png > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > basis
chmod +x basis
