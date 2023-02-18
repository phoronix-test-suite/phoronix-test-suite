#!/bin/sh
tar -xf c-blosc2-2.3.0.tar.gz
cd c-blosc2-2.3.0/
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
cd c-blosc2-2.3.0/build/bench
./b2bench \$@ suite \$NUM_CPU_CORES > \$LOG_FILE
echo \$? > ~/test-exit-status" > blosc
chmod +x blosc
