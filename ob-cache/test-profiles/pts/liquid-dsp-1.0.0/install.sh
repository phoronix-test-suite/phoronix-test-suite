#!/bin/sh

tar -xf liquid-dsp-20210131.tar.xz
cd liquid-dsp-20210131
./bootstrap.sh
./configure --prefix=$HOME/liquid/
make -j $NUM_CPU_CORES
make install

cd ~
cc -O3 $CFLAGS -o benchmark_threaded benchmark_threaded.c -pthread -lm -lc -lliquid -I$HOME/liquid/include/ -L/$HOME/liquid/lib/
echo $? > ~/install-exit-status

echo "#!/bin/sh
LD_LIBRARY_PATH=\$HOME/liquid/lib ./benchmark_threaded -t 20 -m threads \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > liquid-dsp
chmod +x liquid-dsp
