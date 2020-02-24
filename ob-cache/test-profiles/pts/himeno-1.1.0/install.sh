#!/bin/sh

tar -jxf himenobmtxpa.tar.bz2
cc himenobmtxpa.c -O3 $CFLAGS -o himenobmtxpa

echo "#!/bin/sh
./himenobmtxpa s > \$LOG_FILE 2>&1" > himeno
chmod +x himeno
