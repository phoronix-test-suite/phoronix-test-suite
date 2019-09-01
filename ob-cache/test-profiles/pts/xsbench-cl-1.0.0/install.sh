#!/bin/sh

tar -xjvf ComputeApps-20170706.tar.bz2
cd ComputeApps/xsbench-cl/src
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ComputeApps/xsbench-cl/src
./XSBench -l 30000000 \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > xsbench-cl
chmod +x xsbench-cl
