#!/bin/sh

tar -xvf bullet-2.75.tgz
cd bullet-2.75/
cmake .
make
echo \$? > ~/test-exit-status
cd ..

echo "#!/bin/sh
cd bullet-2.75/Demos/Benchmarks
./AppBenchmarks > \$LOG_FILE 2>&1
echo \"\n\" >> \$LOG_FILE
echo \$? > ~/test-exit-status" > bullet
chmod +x bullet
