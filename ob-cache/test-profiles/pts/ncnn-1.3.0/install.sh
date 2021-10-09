#!/bin/sh

tar -xf ncnn-20210720.tar.gz

tar -xf glslang-11.5.0.tar.gz
cp -va glslang-11.5.0/* ncnn-20210720/glslang/

cd ncnn-20210720

# remove int8 tests
sed -i -e "/benchmark(\".*_int8\"/d" benchmark/benchncnn.cpp

mkdir build
cd build

cmake -DNCNN_VULKAN=ON  -DNCNN_BUILD_TOOLS=OFF -DNCNN_BUILD_EXAMPLES=OFF ..
# try to build cpu-only test on system without vulkan development files
is_cmake_ok=$?
if [ $is_cmake_ok -ne 0 ]; then
    cmake -DNCNN_VULKAN=OFF -DNCNN_BUILD_TOOLS=OFF -DNCNN_BUILD_EXAMPLES=OFF ..
fi
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cp ../benchmark/*.param benchmark/

cd ~/
cat>ncnn<<EOT
#!/bin/sh
cd ncnn-20210720/build/benchmark
./benchncnn 250 \$NUM_CPU_CORES 0 \$@ 0  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x ncnn
