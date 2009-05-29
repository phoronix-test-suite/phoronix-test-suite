#!/bin/sh

tar -xvf blogbench-1.0.tar.gz
mkdir $HOME/scratch
cd blogbench-1.0/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cd blogbench-1.0/
./src/blogbench -d \$HOME/scratch > \$LOG_FILE 2>&1" > blogbench
chmod +x blogbench
