#!/bin/sh

mkdir env


tar -xjvf gmp-6.1.2.tar.bz2
cd gmp-6.1.2
./configure --prefix=$HOME/env
make -j $NUM_CPU_JOBS
make install

cd ~
tar -xjvf mpfr-4.0.0.tar.bz2
cd mpfr-4.0.0/
./configure --prefix=$HOME/env --with-gmp=$HOME/env
make -j $NUM_CPU_JOBS
make install

cd ~
tar -xzvf mpc-1.1.0.tar.gz
cd mpc-1.1.0/
./configure --prefix=$HOME/env --with-gmp=$HOME/env
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install

cd ~

echo "#!/bin/sh
cd mpc-1.1.0/
make bench > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mpcbench
chmod +x mpcbench
