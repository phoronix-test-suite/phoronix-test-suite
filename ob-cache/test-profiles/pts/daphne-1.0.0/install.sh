#!/bin/sh

tar -xf daphne-benchmark-20200623.tar.xz

cd daphne-benchmark-master/data
unzip -o ../../daphne-data-full.zip
tar -xf testcases_full.tgz
rm -f testcases_full.tgz

cd ~/daphne-benchmark-master
make opencl
make openmp
echo $? > ~/install-exit-status

export PATH=/usr/local/cuda/bin:$PATH
make cuda

cd ~/
echo "#!/bin/sh
cd daphne-benchmark-master/src/\$1/\$2
./kernel > \$LOG_FILE 2>&1" > daphne
chmod +x daphne
