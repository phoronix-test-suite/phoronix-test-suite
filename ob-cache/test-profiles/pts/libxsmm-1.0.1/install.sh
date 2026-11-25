#!/bin/bash
tar -xf libxsmm-2.1.0.tar.gz
cd libxsmm-2.1.0/
make libs -j $NUM_CPU_CORES && make -C samples/xgemm gemm_kernel_parallel -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
cat << 'EOF' > libxsmm 
#!/bin/sh
export OMP_SCHEDULE=STATIC
export OMP_WAIT_POLICY=ACTIVE
export OMP_PLACES=CORES
export OMP_STACKSIZE=8M 
export OMP_NUM_THREADS=${NUM_CPU_PHYSICAL_CORES:-$(lscpu -p=CORE,SOCKET | awk -F, '!/^#/ { print $1 "," $2 }' | sort -u | wc -l)}
LOG_FILE=${LOG_FILE:-`pwd`/libxsmm.log}
PARAMS=${@:-"I8 I8 I32 I32 64 64 64 64 64 64"}
# The benchmark uses weak scaling so it depends on core frequency and FMA/systolic throughput
# Set the number of repetitions for the benchmark based on the data type and parameters
# There should be enough repetitions to saturate CPUs and get a stable measurement
REPETITIONS=50000
case " $PARAMS " in
    *" I8 I8 "*|*" BF16 BF16 "*) REPETITIONS=1000000 ;;
esac
case " $PARAMS " in
    *" 32 32 32 32 32 32 "*) REPETITIONS=$((REPETITIONS * 8)) ;;
esac
PARAMS="$PARAMS 1 1 0 0 0 0 1 0 0 nopf strdbr 48 1 $REPETITIONS 1"
echo "Running libxsmm with command line: ./gemm_kernel_parallel $PARAMS"
cd libxsmm-2.1.0/samples/xgemm
./gemm_kernel_parallel $PARAMS > $LOG_FILE 2>&1
run_status=$?
thread_gflops=$(awk '/GFLOPS/ {print $NF; exit}' "$LOG_FILE")
if [ -n "$thread_gflops" ]; then
    aggregate_gflops=$(awk -v gflops="$thread_gflops" -v threads="$OMP_NUM_THREADS" 'BEGIN {printf "%.3f", gflops * threads}')
    echo "performance: $aggregate_gflops GFLOPS/s" >> "$LOG_FILE"
else
    echo "Error: no GFLOPS value found in $LOG_FILE" >> "$LOG_FILE"
    run_status=1
fi
echo "$run_status" > ~/test-exit-status
EOF
chmod +x libxsmm 
