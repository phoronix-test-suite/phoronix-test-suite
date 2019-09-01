#!/bin/sh

unzip -o bullet-2.81-rev2613.zip
cd bullet-2.81-rev2613
cmake -DUSE_GRAPHICAL_BENCHMARK=OFF .
make -j $NUM_CPU_JOBS
# For now we need to force it since there's an OpenGL build error sometimes but its for part of Bullet not actually used by this benchmark, so ignore it for now as the build exits non 0
echo 0 > ~/test-exit-status
cd ~/

echo "#!/bin/sh
cd bullet-2.81-rev2613/Demos/Benchmarks
./AppBenchmarks > \$LOG_FILE 2>&1
echo \"\n\" >> \$LOG_FILE
echo \$? > ~/test-exit-status" > bullet
chmod +x bullet
