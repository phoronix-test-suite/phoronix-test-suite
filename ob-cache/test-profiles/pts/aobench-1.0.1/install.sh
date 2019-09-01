#!/bin/sh

unzip -o aobench-20180207.zip
cc ao.c -o ao -lm -O3 $CFLAGS
echo $? > ~/install-exit-status

echo "#!/bin/sh
./ao > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > aobench
chmod +x aobench
