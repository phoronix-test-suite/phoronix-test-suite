#!/bin/sh
mkdir $HOME/iperf-install
tar -zxvf iperf-3.14.tar.gz
cd iperf-3.14/
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
rm -rf iperf-3.14/
cd ~
echo "#!/bin/sh
cd iperf-install/bin
# start server if doing localhost testing
./iperf3 -s &
IPERF_SERVER_PID=\$!
sleep 3
./iperf3 \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status
kill \$IPERF_SERVER_PID" > iperf
chmod +x iperf
