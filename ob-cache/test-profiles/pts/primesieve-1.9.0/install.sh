#!/bin/sh

version=8.0
tar xvf primesieve-$version.tar.gz
cd primesieve-$version

cmake . -DBUILD_SHARED_LIBS=OFF
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
fi
cd ~

echo "#!/bin/sh
primesieve-$version/./primesieve \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > primesieve-test
chmod +x primesieve-test
