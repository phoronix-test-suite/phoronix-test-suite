#!/bin/sh

mkdir $HOME/gpgerror
tar -jxf libgpg-error-1.7.tar.bz2
cd libgpg-error-1.7/
./configure --prefix=$HOME/gpgerror
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libgpg-error-1.7/

tar -jxf libgcrypt-1.4.4.tar.bz2
cd libgcrypt-1.4.4/
./configure --with-gpg-error-prefix=$HOME/gpgerror
make -j $NUM_CPU_JOBS
echo \$? > ~/test-exit-status
cd ..

echo "#!/bin/sh
./libgcrypt-1.4.4/tests/benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gcrypt
chmod +x gcrypt


