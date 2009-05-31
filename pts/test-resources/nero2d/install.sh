#!/bin/sh

mkdir $HOME/nero2d_

tar -xvf nero2d-2.0.2.tar.gz

cd nero2d-2.0.2/
./configure --prefix=$HOME/nero2d_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf nero2d-2.0.2/

echo "#!/bin/sh
\$TIMER_START
./nero2d_/bin/nero2d \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
\$TIMER_STOP" > nero2d
chmod +x nero2d
