#!/bin/sh

tar -xzvf FAHBench-2.3.2-Linux.tar.gz

cd ~
echo "#!/bin/sh
cd FAHBench-2.3.2-Linux/bin
./FAHBench-cmd \$@ > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status" > fahbench
chmod +x fahbench
