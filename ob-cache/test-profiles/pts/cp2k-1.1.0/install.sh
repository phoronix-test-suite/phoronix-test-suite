#!/bin/sh

chmod +x cp2k-8.1-Linux-x86_64.ssmp
tar -xjf cp2k-8.1.tar.bz2

echo "#!/bin/sh
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES cp2k-8.1-Linux-x86_64.ssmp -i cp2k-8.1/benchmarks/Fayalite-FIST/fayalite.inp > \$LOG_FILE 2>&1" > cp2k
chmod +x cp2k
