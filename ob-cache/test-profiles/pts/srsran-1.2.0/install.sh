#!/bin/sh
tar -xf srsRAN-release_22_04_1.tar.gz
cd srsRAN-release_22_04_1/
mkdir build
cd build
cmake -DENABLE_GUI=OFF ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd srsRAN-release_22_04_1/build/
./\$@ 2>&1  | sed 's/Rx@/ /' > \$LOG_FILE
echo \$? > ~/test-exit-status" > srsran
chmod +x srsran
