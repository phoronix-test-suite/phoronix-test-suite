#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/bonnie_

tar -xvf bonnie++-1.03d.tgz
cd bonnie++-1.03d/
./configure --prefix=$THIS_DIR/bonnie_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf bonnie++-1.03d/

echo "#!/bin/sh
echo \$1 > TEST_TYPE
BONNIERAM=\$((\$SYS_MEMORY * 2))
rm -rf scratch_dir/
mkdir scratch_dir/
./bonnie_/sbin/bonnie++ -d scratch_dir/ -s \$BONNIERAM 2>&1" > bonnie
chmod +x bonnie
