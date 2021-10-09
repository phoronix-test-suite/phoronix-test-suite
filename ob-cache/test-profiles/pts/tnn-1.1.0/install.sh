#!/bin/sh

tar -xf TNN-0.3.0.tar.gz
cd TNN-0.3.0/

mkdir build
cd build

cmake .. -DTNN_TEST_ENABLE=ON -DTNN_CPU_ENABLE=ON -DCMAKE_BUILD_TYPE=Release -DTNN_BENCHMARK_MODE=ON -DTNN_OPENMP_ENABLE=ON -DTNN_OPENCL_ENABLE=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
cat>tnn<<EOT
#!/bin/sh
cd TNN-0.3.0/build/
./test/TNNTest -wc 10 -ic 60 -th \$NUM_CPU_CORES 0 -mt TNN \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x tnn
