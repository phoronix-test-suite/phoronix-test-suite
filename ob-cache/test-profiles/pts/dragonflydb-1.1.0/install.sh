#!/bin/bash
cd ~
tar -xf memtier_benchmark-2.0.0.tar.gz
cd memtier_benchmark-2.0.0/
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf dragonfly-1.6.2-aarch64.tar.gz
	mv dragonfly-aarch64 dragonfly
else
	tar -xf dragonfly-1.6.2-x86_64.tar.gz
	mv dragonfly-x86_64 dragonfly
fi
echo "#!/bin/sh
./dragonfly > \$LOG_FILE &
SERVER_PID=\$!
sleep 5
cd ~/memtier_benchmark-2.0.0
./memtier_benchmark --threads=\$NUM_CPU_PHYSICAL_CORES --hide-histogram \$@ >> \$LOG_FILE 2>&1
kill \$SERVER_PID" > dragonflydb
chmod +x dragonflydb
