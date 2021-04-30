#!/bin/sh

rm -rf config build DDNet-15.3.1
tar xvf DDNet-15.3.1.tar.xz
unzip ddnet-libs-master-20201119.zip
rm -f DDNet-15.3.1/ddnet-libs
mv ddnet-libs-master DDNet-15.3.1/ddnet-libs

mkdir -p build/data/demos
cp Multeasymap_bench.demo RaiNyMore2_bench.demo build/data/demos
cd build
cmake -DCMAKE_BUILD_TYPE=Release ../DDNet-15.3.1
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
