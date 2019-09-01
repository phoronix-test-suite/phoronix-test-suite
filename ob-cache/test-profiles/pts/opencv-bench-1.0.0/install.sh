#!/bin/sh

tar -xvf opencv-benchmarks-1.0.tar.gz
cd opencv-benchmarks-0.1/
cmake .
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd opencv-benchmarks-0.1/
./main.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > opencv-bench
chmod +x opencv-bench
