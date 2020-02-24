#!/bin/sh

mkdir $HOME/iperf-install
tar -zxvf iperf-3.1.3-source.tar.gz
cd iperf-3.1.3

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

./configure --prefix=$HOME/iperf-install CFLAGS="$CFLAGS"
make -j $NUM_CPU_CORES
make install
echo $? > ~/install-exit-status
cd ~
rm -rf iperf-3.1.3

cd ~
echo "#!/bin/sh
cd iperf-install/bin
./iperf3 \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > iperf
chmod +x iperf
