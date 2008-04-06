#!/bin/sh

cd $1

if [ ! -f libxml2.tar.gz ]
  then
     wget ftp://xmlsoft.org/libxml2/libxml2-2.6.31.tar.gz -O libxml2.tar.gz
fi

if [ ! -f php5.tar.bz2 ]
  then
     wget http://us3.php.net/get/php-5.2.5.tar.bz2/from/us2.php.net/mirror -O php5.tar.bz2
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/libxml2

tar -xvf libxml2.tar.gz

cd libxml2-2.6.31/
./configure --prefix=$THIS_DIR/libxml2
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libxml2-2.6.31/

echo "#!/bin/sh

if [ ! -f php5.tar.bz2 ]
  then
	echo \"PHP5 Not Downloaded... Build Fails.\"
	exit
fi

rm -rf php-5.2.5/
tar -xjf php5.tar.bz2
cd php-5.2.5/
./configure --with-libxml-dir=$THIS_DIR/libxml2 > /dev/null
sleep 3
/usr/bin/time -f \"PHP Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-php

chmod +x time-compile-php
