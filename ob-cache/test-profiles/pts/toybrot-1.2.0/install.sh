#!/bin/sh

tar -xf toyBrot-20210215.tar.xz
cd toyBrot-master/

sed -i 's/-target x86_64-pc-linux-gnu -fopenmp=libomp -march=znver1/-fopenmp -march=native/'  raymarched/OMP/CMakeLists.txt 

mkdir build
cd build
cmake .. -DCMAKE_BUILD_TYPE=Release -DOMP_FLAGS="-fopenmp -O3 -march=native"
make -j $NUM_CPU_CORES
# echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd toyBrot-master/build/
./\$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > toybrot
chmod +x toybrot
