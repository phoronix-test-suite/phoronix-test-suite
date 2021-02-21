#!/bin/sh

rm -rf config build ddnet-pr-benchmark
unzip ddnet-pr-benchmark.zip
unzip ddnet-libs-master-20201119.zip
rm -f ddnet-pr-benchmark/ddnet-libs
mv ddnet-libs-master ddnet-pr-benchmark/ddnet-libs

mkdir -p build/data/demos
cp Multeasymap_bench.demo RaiNyMore2_bench.demo build/data/demos
cd build
cmake -DCMAKE_BUILD_TYPE=Release ../ddnet-pr-benchmark
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
# Make sure not to use/overwrite user config, only use our custom config
echo -e "add_path $HOME/config\nadd_path \$DATADIR\nadd_path \$CURRENTDIR" > storage.cfg
cd ..

echo "#!/bin/sh
rm -f bench.cfg
for i in \"\$@\"; do echo \$i >> bench.cfg; done
echo \"benchmark_quit 60 \$LOG_FILE\" >> bench.cfg
build/DDNet -f bench.cfg > ~/test-log 2>&1
" > ddnet
chmod +x ddnet
