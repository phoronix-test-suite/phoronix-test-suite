#!/bin/sh

tar -xf Botan-2.17.3.tar.xz

cd Botan-2.17.3
python3 ./configure.py
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd Botan-2.17.3
LD_LIBRARY_PATH=.:\$LD_LIBRARY_PATH ./botan speed \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > botan
chmod +x botan
