#!/bin/sh

tar -xf srsLTE-release_20_10_1.tar.gz
cd srsLTE-release_20_10_1
mkdir build
cd build
cmake -DENABLE_GUI=OFF ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd srsLTE-release_20_10_1/build/
./\$@ 2>&1  | sed 's/Rx@/ /' > \$LOG_FILE
echo \$? > ~/test-exit-status" > srslte
chmod +x srslte
