#!/bin/sh

tar -xf ngspice-34.tar.gz
tar -xf iscas85Circuits-1.tar.xz

cd ngspice-34
./configure --enable-openmp
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh

cd ngspice-34
./src/ngspice \$@ > \$LOG_FILE" > ngspice
chmod +x ngspice
