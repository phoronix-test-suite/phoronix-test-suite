#!/bin/sh
tar -xf c-blosc2-2.11.0.tar.gz
cd c-blosc2-2.11.0/
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release ..
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd c-blosc2-2.11.0/build/bench
./b2bench \$1 \$2 suite \$NUM_CPU_CORES \$3 > \$LOG_FILE
echo \$? > ~/test-exit-status" > blosc
chmod +x blosc
