#!/bin/sh

tar -xf securemark-tls-1.0.4.tar.gz
cd securemark-tls-1.0.4/examples/selfhosted/
mkdir build
cd build
cmake -DSELFHOSTED=1 ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./securemark-tls-1.0.4/examples/selfhosted/build/sec-tls > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > securemark
chmod +x securemark
