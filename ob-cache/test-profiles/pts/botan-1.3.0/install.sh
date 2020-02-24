#!/bin/sh

tar -xf Botan-2.6.0.tgz

cd Botan-2.6.0
./configure.py
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd Botan-2.6.0
./botan speed \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > botan
chmod +x botan


