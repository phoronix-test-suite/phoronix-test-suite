#!/bin/sh

mkdir $HOME/nero2d_

tar -zxvf nero2d-2.0.2-pts1.tar.gz
cd nero2d-2.0.2/
./configure --prefix=$HOME/nero2d_ --enable-mpi
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf nero2d-2.0.2/

echo "#!/bin/sh
mpirun ./nero2d_/bin/nero2d \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nero2d
chmod +x nero2d
