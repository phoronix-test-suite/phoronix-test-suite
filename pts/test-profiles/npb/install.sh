#!/bin/sh

tar -zxvf NPB3.3.tar.gz
tar -zxvf npb-omp-make-def-1.tar.gz

mv -f make.def NPB3.3/NPB3.3-OMP/config/
cd NPB3.3/NPB3.3-OMP/

make bt CLASS=A
make cg CLASS=B
make ep CLASS=B
make ft CLASS=B
make is CLASS=C
make lu CLASS=A
make mg CLASS=B
make sp CLASS=A
make ua CLASS=A

echo \$? > ~/test-exit-status

cd ~
echo "#!/bin/sh
cd NPB3.3/NPB3.3-OMP/
export OMP_NUM_THREADS=\$NUM_CPU_CORES
./bin/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > npb
chmod +x npb
