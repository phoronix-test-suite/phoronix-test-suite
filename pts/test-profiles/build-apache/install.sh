#!/bin/sh

echo "#!/bin/sh
cd httpd-2.2.17/
make -s -j \$NUM_CPU_JOBS 2>&1" > time-compile-apache

chmod +x time-compile-apache
