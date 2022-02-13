#!/bin/bash

tar -zxvf graph500-graph500-3.0.0.tar.gz
cd graph500-graph500-3.0.0/src

if [[ $(echo $NUM_CPU_CORES | awk '{lg = log($1) / log(2)} lg == int(lg)') ]]; then
    echo "NUM_CPU_CORES is a power of 2"
else
    # Otherwise build problem...
    echo "NUM_CPU_CORES is NOT a power of 2"
    CFLAGS="$CFLAGS -DPROCS_PER_NODE_NOT_POWER_OF_TWO"
fi

# -fcommon is needed for GCC 10+ compiler support nicely building
sed -i "s/CFLAGS = /CFLAGS = -fcommon $CFLAGS /g" Makefile

make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd graph500-graph500-3.0.0/src
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./graph500_reference_bfs_sssp \$1 > \$LOG_FILE
echo \$? > ~/test-exit-status" > graph500
chmod +x graph500
