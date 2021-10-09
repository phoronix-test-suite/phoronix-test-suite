#!/bin/sh

tar -xf Core-3.5.1.tar.gz
tar -xf yafarayRender-sample-1.tar.xz

cd Core-3.5.1
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release  ../
make -j $NUM_CPU_CORES
make DESTDIR=../install install
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./Core-3.5.1/install/usr/local/bin/yafaray-xml -t \$NUM_CPU_CORES yafarayRender.xml > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > yafaray
chmod +x yafaray
