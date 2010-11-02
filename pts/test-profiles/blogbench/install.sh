#!/bin/sh

mkdir $HOME/blogbench_/

tar -zxvf blogbench-1.0.tar.gz
cd blogbench-1.0/
./configure --prefix=$HOME/blogbench_/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf blogbench-1.0/

echo "#!/bin/sh
rm -rf \$HOME/scratch/
mkdir \$HOME/scratch/
./blogbench_/bin/blogbench -d \$HOME/scratch > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -rf \$HOME/scratch/" > blogbench
chmod +x blogbench
