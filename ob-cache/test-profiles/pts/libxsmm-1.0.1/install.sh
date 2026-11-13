#!/bin/bash
tar -xf libxsmm-2.1.0.tar.gz
cd libxsmm-2.1.0/
make smm -j $NUM_CPU_CORES 
echo $? > ~/install-exit-status
cd ~
cat << 'EOF' > libxsmm 
#!/bin/sh
export OMP_SCHEDULE=STATIC
export OMP_WAIT_POLICY=ACTIVE
export OMP_PLACES=CORES
export OMP_NUM_THREADS=${NUM_CPU_PHYSICAL_CORES:-$(lscpu -p=CORE,SOCKET | awk -F, '!/^#/ { print $1 "," $2 }' | sort -u | wc -l)}
LOG_FILE=${LOG_FILE:-`pwd`/libxsmm.log}
cd libxsmm-2.1.0/samples/utilities/smmbench
./specialized 0 $@ 0 500 $@ > $LOG_FILE 2>&1
echo $? > ~/test-exit-status
EOF
chmod +x libxsmm

