#!/bin/sh
tar -xf aircrack-ng-1.3.tar.gz
cd aircrack-ng-1.3
if [ "$OS_TYPE" = "BSD" ]
then
	if [ -e /usr/local/lib/libcrypto.so ]
	then
		env MAKE=gmake CFLAGS=-I/usr/local/include LDFLAGS=-L/usr/local/lib ./autogen.sh
	else
		env MAKE=gmake ./autogen.sh
	fi
	gmake -j $NUM_CPU_CORES
else
	./autogen.sh
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd aircrack-ng-1.3
./src/aircrack-ng -p \$NUM_CPU_CORES \$@  2>&1 | tr '\\r' '\\n' | awk -v max=0 '{if(\$1>max){max=\$1}}END{print max \" k/s\"}' > \$LOG_FILE
echo \$? > ~/test-exit-status" > aircrack-ng
chmod +x aircrack-ng
