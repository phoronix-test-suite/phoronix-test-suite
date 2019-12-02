#!/bin/sh

tar -xf himenobmtxpa-2.tar.xz

if [ $OS_TYPE = "Linux" ]
then
    if grep avx2 /proc/cpuinfo > /dev/null
    then
	export CFLAGS="$CFLAGS -mavx2"
    fi
fi

cc himenobmtxpa.c -O3 $CFLAGS -o himenobmtxpa
echo $? > ~/install-exit-status

echo "#!/bin/sh
./himenobmtxpa s > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > himeno
chmod +x himeno
