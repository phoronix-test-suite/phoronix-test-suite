#!/bin/sh

echo "#!/bin/sh
rm -rf httpd-2.2.11/
tar -zxvf httpd-2.2.11.tar.gz
cd httpd-2.2.11/
./configure > /dev/null
sleep 3

\$TIMER_START
make -s -j \$NUM_CPU_JOBS 2>&1
\$TIMER_STOP" > time-compile-apache

chmod +x time-compile-apache
