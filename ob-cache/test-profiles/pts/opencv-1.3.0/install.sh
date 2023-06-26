#!/bin/sh
tar -xf opencv-4.7.0.tar.gz
tar -xf opencv_extra-4.7.0.tar.gz
cd opencv-4.7.0
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release -DWITH_OPENCL=OFF ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd opencv-4.7.0/build/bin
export OPENCV_TEST_DATA_PATH=\$HOME/opencv_extra-4.7.0/testdata/
./opencv_perf_\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > opencv
chmod +x opencv
