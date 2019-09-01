#!/bin/sh

tar -zxvf ramsmp-3.5.0.tar.gz

cd ramsmp-3.5.0/
export CFLAGS="-O3 -march=native $CFLAGS"
cc $CFLAGS -o ramsmp fltmark.c fltmem.c intmark.c intmem.c ramsmp.c
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd ramsmp-3.5.0/
./ramsmp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ramspeed
chmod +x ramspeed

