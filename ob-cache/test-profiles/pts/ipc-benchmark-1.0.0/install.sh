#!/bin/sh

unzip -o ipc_benchmark-20200228.zip
cd ipc_benchmark-master/
make pipe
make fifo
make socketpair
make tcp
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd ipc_benchmark-master/
./\$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > ipc-benchmark
chmod +x ipc-benchmark
