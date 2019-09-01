#!/bin/sh

tar -jxf himenobmtxpa.tar.bz2

if [ $OS_TYPE = "Linux" ]
then
    if grep avx2 /proc/cpuinfo > /dev/null
    then
	export CFLAGS="$CFLAGS -mavx2"
    fi
fi

cc himenobmtxpa.c -O3 $CFLAGS -o himenobmtxpa

echo "#!/bin/sh
./himenobmtxpa s > \$LOG_FILE 2>&1" > himeno
chmod +x himeno
