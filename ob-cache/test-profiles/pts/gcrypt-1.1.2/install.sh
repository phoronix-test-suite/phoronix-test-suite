#!/bin/sh

mkdir $HOME/gpgerror
tar -jxf libgpg-error-1.41.tar.bz2
cd libgpg-error-1.41/
./configure --prefix=$HOME/gpgerror
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf libgpg-error-1.41/

tar -jxf libgcrypt-1.9.1.tar.bz2
cd libgcrypt-1.9.1/
./configure --with-gpg-error-prefix=$HOME/gpgerror
make -j $NUM_CPU_CORES
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./libgcrypt-1.9.1/tests/benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gcrypt
chmod +x gcrypt


