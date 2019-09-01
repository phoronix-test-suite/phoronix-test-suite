#!/bin/sh

unzip -o tungsten-20190812.zip

cd tungsten-master/
./setup_builds.sh
cd build/release
make -j $NUM_CPU_CORES
# echo $? > ~/install-exit-status
cd ~

echo "#!/bin/bash
cd tungsten-master/build/release
./tungsten -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > tungsten
chmod +x tungsten
