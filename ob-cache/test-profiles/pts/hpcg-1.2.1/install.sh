#!/bin/sh

tar -zxvf hpcg-3.1.tar.gz
cd hpcg-3.1
make arch=Linux_MPI
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd hpcg-3.1/bin/
rm -f HPCG-Benchmark*.txt
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./xhpcg
echo \$? > ~/test-exit-status

cat HPCG-Benchmark*.txt > \$LOG_FILE" > hpcg
chmod +x hpcg
