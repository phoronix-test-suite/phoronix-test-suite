#!/bin/sh

tar -xf nettle-3.8.tar.gz
cd nettle-3.8

./configure --prefix=$HOME/install

if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	gmake install
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	make install
	echo $? > ~/install-exit-status
fi

cd ~/
echo "#!/bin/sh
cd nettle-3.8/examples/
LD_LIBRARY_PATH=\$HOME/install/lib64:\$HOME/install/lib:\$LD_LIBRARY_PATH ./nettle-benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nettle
chmod +x nettle
