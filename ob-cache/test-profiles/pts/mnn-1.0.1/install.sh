#!/bin/sh

tar -xf MNN-20200917.tar.xz
rm -rf MNN-master
mv MNN MNN-master
cd MNN-master
cd schema
./generate.sh
cd ..
mkdir build
cd build
cmake .. -DMNN_BUILD_BENCHMARK=true 
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
cat>mnn<<EOT
#!/bin/sh
cd MNN-master/build
./benchmark.out ../benchmark/models/ 1000 100 0 \$NUM_CPU_CORES > \$LOG_FILE
echo \$? > ~/test-exit-status
EOT
chmod +x mnn

