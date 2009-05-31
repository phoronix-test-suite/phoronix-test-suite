#!/bin/sh

tar -xvf minion-0.8.1-src.tar.gz
cd minion-0.8.1/
mkdir build
cd build/
cmake ..
make minion -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ../..
rm -rf minion-0.8.1/build/CMakeFiles/

echo "#!/bin/sh
cd minion-0.8.1/
./build/minion \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > minion
chmod +x minion
