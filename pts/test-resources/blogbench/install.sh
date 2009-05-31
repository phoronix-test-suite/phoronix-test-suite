#!/bin/sh

mkdir $HOME/scratch/
mkdir $HOME/blogbench_/

tar -xvf blogbench-1.0.tar.gz
cd blogbench-1.0/
./configure --prefix=$HOME/blogbench_/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf blogbench-1.0/

echo "#!/bin/sh
./blogbench_/bin/blogbench -d \$HOME/scratch > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > blogbench
chmod +x blogbench
