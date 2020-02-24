#!/bin/sh
tar -xf BenchmarkMutex-1.tar.xz
c++ -std=c++17  BenchmarkMutex.cpp -o BenchmarkMutex -lbenchmark -pthread
echo $? > ~/install-exit-status

echo "#!/bin/sh
./BenchmarkMutex \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > mutex
chmod +x mutex
