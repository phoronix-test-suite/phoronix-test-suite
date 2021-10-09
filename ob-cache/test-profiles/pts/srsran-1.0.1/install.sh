#!/bin/sh

tar -xf srsRAN-release_21_04.tar.gz
cd srsRAN-release_21_04
mkdir build
cd build
cmake -DENABLE_GUI=OFF ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd srsRAN-release_21_04/build/
./\$@ 2>&1  | sed 's/Rx@/ /' > \$LOG_FILE
echo \$? > ~/test-exit-status" > srsran
chmod +x srsran
