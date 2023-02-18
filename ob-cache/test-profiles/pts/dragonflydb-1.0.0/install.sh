#!/bin/bash

cd ~
tar -xf memtier_benchmark-1.4.0.tar.gz
cd memtier_benchmark-1.4.0/
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

if [ $OS_ARCH = "aarch64" ]
then
	tar -xf dragonfly-0.6.0-aarch64.tar.gz
	mv dragonfly-aarch64 dragonfly
else
	tar -xf dragonfly-0.6.0-x86_64.tar.gz
	mv dragonfly-x86_64 dragonfly
fi

echo "#!/bin/sh
./dragonfly &
SERVER_PID=\$!
sleep 6

cd ~/memtier_benchmark-1.4.0/
./memtier_benchmark --hide-histogram \$@ > \$LOG_FILE
kill \$SERVER_PID" > dragonflydb
chmod +x dragonflydb
