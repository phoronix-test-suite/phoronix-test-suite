#!/bin/sh

mkdir $HOME/libxml2

tar -xvf libxml2-2.6.31.tar.gz

cd libxml2-2.6.31/
./configure --prefix=$HOME/libxml2 > /dev/null
make -s -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libxml2-2.6.31/

echo "#!/bin/sh

rm -rf php-5.2.9/
tar -xjf php-5.2.9.tar.bz2
cd php-5.2.9/
./configure --with-libxml-dir=\$HOME/libxml2 > /dev/null
sleep 3
\$TIMER_START
make -s -j \$NUM_CPU_JOBS 2>&1
\$TIMER_STOP" > time-compile-php

chmod +x time-compile-php
