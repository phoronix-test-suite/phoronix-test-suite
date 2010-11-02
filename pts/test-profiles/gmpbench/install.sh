#!/bin/sh

mkdir $HOME/gmp_

tar -zxvf gexpr.c.tar.gz
tar -zxvf gmp-4.3.0.tar.gz
cd gmp-4.3.0/
./configure --prefix=$HOME/gmp_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gmp-4.3.0/

tar -zxvf gmpbench-0.1.tar.gz
cp gexpr.c gmpbench-0.1/

cp gmp_/include/gmp.h gmpbench-0.1/

cd gmpbench-0.1/
gcc -lm gexpr.c -o gexpr
echo $? > ~/install-exit-status

cd ..

echo "#!/bin/sh
cd gmpbench-0.1/
LIBS=$HOME/gmp_/lib/libgmp.so.3.5.0 PATH=.:$PATH ./runbench > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gmpbench
chmod +x gmpbench
