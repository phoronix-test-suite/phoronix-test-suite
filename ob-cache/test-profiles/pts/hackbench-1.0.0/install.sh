#!/bin/sh

unzip -o hackbench-20180115.zip
cc hackbench.c -o hackbench_bin -lpthread $CFLAGS
echo $? > ~/install-exit-status

echo "#!/bin/sh
./hackbench_bin \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > hackbench
chmod +x hackbench
