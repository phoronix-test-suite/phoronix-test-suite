#!/bin/sh
unzip -o srsRAN_Project-2f90c8b60e9396a7aed59645c98dbcbccda2bf7c.zip
cd srsRAN_Project-2f90c8b60e9396a7aed59645c98dbcbccda2bf7c
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release -DCMAKE_CXX_FLAGS="-O3 -Wno-error" -DENABLE_WERROR=OFF ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd srsRAN_Project-2f90c8b60e9396a7aed59645c98dbcbccda2bf7c/build/
./\$@ 2>&1 > \$LOG_FILE
echo \$? > ~/test-exit-status" > srsran
chmod +x srsran
