#!/bin/sh

chmod +x cp2k-8.1-Linux-x86_64.ssmp
tar -xjf cp2k-8.1.tar.bz2

mv cp2k-8.1/benchmarks .

echo "#!/bin/bash

export OMP_NUM_THREADS=1
# Eventually just use the CPU_THREADS_PER_CORE env var exported since PTS 10.3
if [ \"\$NUM_CPU_CORES\" -gt \"\$NUM_CPU_PHYSICAL_CORES\" ]; then
    export OMP_NUM_THREADS=\$((NUM_CPU_CORES / NUM_CPU_PHYSICAL_CORES))
fi

mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES cp2k-8.1-Linux-x86_64.ssmp \$@ > \$LOG_FILE 2>&1" > cp2k
chmod +x cp2k
