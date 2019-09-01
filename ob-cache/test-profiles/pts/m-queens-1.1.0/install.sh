#!/bin/sh

tar -zxvf m-queens-1.2.tar.gz

cd m-queens-1.2/

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O2 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

# fixed board size N=19
g++ -fopenmp $CFLAGS main.c -o m-queens.bin
echo $? > ~/install-exit-status

cd ~/

echo "#!/bin/sh
cd m-queens-1.2/
OMP_NUM_THREADS=\$NUM_CPU_CORES ./m-queens.bin \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > m-queens
chmod +x m-queens
