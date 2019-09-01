#!/bin/sh

mkdir $HOME/libxml2

tar -zxvf libxml2-2.6.31.tar.gz

cd libxml2-2.6.31/
./configure --prefix=$HOME/libxml2 > /dev/null
make -s -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libxml2-2.6.31/
rm -rf libxml2/share/

echo "#!/bin/sh
cd php-7.1.9/
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status" > time-compile-php

chmod +x time-compile-php
