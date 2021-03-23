#!/bin/sh

rm -rf MNN-1.1.3
tar -xf MNN-1.1.3.tar.gz
cd MNN-1.1.3
cd schema
./generate.sh
cd ..
mkdir build
cd build
cmake .. -DMNN_BUILD_BENCHMARK=true -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
cat>mnn<<EOT
#!/bin/sh
cd MNN-1.1.3/build
./benchmark.out ../benchmark/models/ 1000 100 0 \$NUM_CPU_CORES > \$LOG_FILE
echo \$? > ~/test-exit-status
EOT
chmod +x mnn

