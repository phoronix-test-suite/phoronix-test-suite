#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/lame_

tar -xvf lame-398.tar.gz
cd lame-398/
./configure --prefix=$THIS_DIR/lame_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf lame-398/

echo "#!/bin/sh
\$TIMER_START
./lame_/bin/lame --silent -h \$TEST_EXTENDS/pts-trondheim.wav /dev/null 2>&1
\$TIMER_STOP" > lame
chmod +x lame
