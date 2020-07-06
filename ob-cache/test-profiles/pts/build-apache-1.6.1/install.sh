#!/bin/sh

echo "#!/bin/sh
cd httpd/
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-apache

chmod +x build-apache
