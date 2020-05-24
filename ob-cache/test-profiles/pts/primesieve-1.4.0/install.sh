#!/bin/sh

version=6.2
tar xvf primesieve-$version.tar.gz
cd primesieve-$version

cmake .
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
primesieve-$version/./primesieve \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > primesieve
chmod +x primesieve
