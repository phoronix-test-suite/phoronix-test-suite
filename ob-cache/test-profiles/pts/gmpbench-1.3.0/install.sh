#!/bin/sh

mkdir $HOME/gmp_

tar -jxvf gmp-6.2.1.tar.bz2
cd gmp-6.2.1/
./configure --prefix=$HOME/gmp_
make -j $NUM_CPU_CORES
make install
cd ~
rm -rf gmp-6.2.1

tar -jxvf gmpbench-0.2.tar.bz2
tar -xvzf gexpr.c.tar.gz

mv gexpr.c gmpbench-0.2/
cp gmp_/include/gmp.h gmpbench-0.2/

cd gmpbench-0.2/
cc -O3 $CFLAGS gexpr.c -o gexpr -lm
LIBS=$HOME/gmp_/lib/libgmp.so PATH=.:$PATH ./runbench
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd gmpbench-0.2/
LIBS=$HOME/gmp_/lib/libgmp.so PATH=.:$PATH ./runbench -n > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gmpbench
chmod +x gmpbench
