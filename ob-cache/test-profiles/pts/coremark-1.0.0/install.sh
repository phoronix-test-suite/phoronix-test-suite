#!/bin/sh

unzip -o coremark-20190727.zip
cd coremark-master
make XCFLAGS="$CFLAGS -DMULTITHREAD=$NUM_CPU_CORES -DUSE_FORK=1" compile
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd coremark-master
./coremark.exe > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > coremark
chmod +x coremark
