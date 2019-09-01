#!/bin/sh
unzip -o CppPerformanceBenchmarks-9.zip 
cd CppPerformanceBenchmarks-master/
make all
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd CppPerformanceBenchmarks-master/
./\$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > cpp-perf-bench
chmod +x cpp-perf-bench
