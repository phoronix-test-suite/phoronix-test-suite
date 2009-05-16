#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/nero2d_

tar -xvf nero2d-2.0.2.tar.gz

cd nero2d-2.0.2/
./configure --prefix=$THIS_DIR/nero2d_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf nero2d-2.0.2/

echo "#!/bin/sh
\$TIMER_START
./nero2d_/bin/nero2d \$@ > \$LOG_FILE 2>&1
\$TIMER_STOP" > nero2d
chmod +x nero2d
