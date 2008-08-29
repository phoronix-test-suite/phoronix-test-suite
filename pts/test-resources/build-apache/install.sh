#!/bin/sh

echo "#!/bin/sh
rm -rf httpd-2.2.8/
tar -xvf httpd-2.2.8.tar.gz
cd httpd-2.2.8/
./configure > /dev/null
sleep 3
/usr/bin/time -f \"Apache Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-apache

chmod +x time-compile-apache
