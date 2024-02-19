#!/bin/sh
tar -xvf OpenCL-Benchmark-1.2.tar.gz
cd OpenCL-Benchmark-1.2
mkdir bin
c++  -o bin/OpenCL-Benchmark src/*.cpp -std=c++17 -pthread -I./src/OpenCL/include -lOpenCL
echo $? > ~/install-exit-status
cd ~/
echo "#!/bin/sh
cd OpenCL-Benchmark-1.2
echo \"\" | ./bin/OpenCL-Benchmark 0 > \$LOG_FILE
echo \$? > ~/test-exit-status" > opencl-benchmark
chmod +x opencl-benchmark
