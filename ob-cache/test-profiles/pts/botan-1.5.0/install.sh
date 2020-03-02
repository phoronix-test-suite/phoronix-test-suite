#!/bin/sh

tar -xf Botan-2.13.0.tar.xz

cd Botan-2.13.0
python3 ./configure.py
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd Botan-2.13.0
./botan speed \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > botan
chmod +x botan


