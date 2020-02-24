#!/bin/sh

unzip -o t-test1c-20171.zip
cc -pthread $CFLAGS -o t-test1_bin t-test1.c
echo $? > ~/install-exit-status

echo "#!/bin/sh
echo \"#define N_THREADS \$@
\" > t-test1-compile.c
cat t-test1.c >> t-test1-compile.c
cc -pthread $CFLAGS -o t-test1_bin t-test1-compile.c

./t-test1_bin > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > t-test1
chmod +x t-test1
