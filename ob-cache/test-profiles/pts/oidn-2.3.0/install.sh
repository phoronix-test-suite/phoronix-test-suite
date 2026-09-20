#!/bin/sh
tar -xf oidn-2.5.0.x86_64.linux.tar.gz
cat << 'EOF' > oidn
#!/bin/bash
# setting non-zero status if any of pipeline operations fail
set -o pipefail
# Benchmark harness expects 0, all exit codes must be 0 to pass
# Dumping and replacing non-zero code(s) to fail the run
update_test_exit_status() {
    [ $1 -ne 0 ] && echo $1 > ~/test-exit-status;
}
# Debug variables to run without PTS
LOG_FILE=${LOG_FILE:="`pwd`/oidn.log"}
NUM_CPU_CORES=${NUM_CPU_CORES:-`nproc`}

cd oidn-2.5.0.x86_64.linux/bin/

# Determine NUMA compute nodes and their count
# if numactl is not available, this will fallback to a single compute node
OIDN_COMPUTE_NODES=(${OIDN_COMPUTE_NODES:=`numactl -H | grep -E "cpus: [0-9]" | awk '{print $2}' | tr '\n' ' '`})
OIDN_COMPUTE_NODE_COUNT=${#OIDN_COMPUTE_NODES[@]}

# CPU device is the default one when no -d/--device is given
OIDN_DEVICE=`echo "$@" | grep -oE '(-d|--device) +[A-Za-z0-9]+' | awk '{print $2}' | tail -1`
OIDN_DEVICE=${OIDN_DEVICE:=cpu}

if [ "$OIDN_DEVICE" == "cpu" ] && [ $OIDN_COMPUTE_NODE_COUNT -gt 1 ]
then
    # OIDN recommends using only one hardware thread per CPU core.
    # OIDN benchmark uses Intel TBB without exploring NUMA awareness.
    # So if NUMA compute nodes are available then denoising/processing one frame set per NUMA node,
    # several frames in parallel maximizing memory locality:
    # * Running 1 benchmark instance per NUMA compute node.
    # * Providing aggregated values to the benchmark harness. See below comments for details.
    echo $? > ~/test-exit-status
    update_test_exit_status $?
    for i in "${OIDN_COMPUTE_NODES[@]}";do
        # Get physical cores on the compute node
        CORES=`lscpu -e=CPU,CORE,NODE | awk -v node=$i '$3==node && !seen[$2]++ {printf "%s,",$1}' | sed 's/,$//'`
        # The function is not thread-safe, running sequentially
        update_test_exit_status $?
        # Affinity is handled by numactl, OIDN pinning is disabled to not interfere with it
        ( numactl -C $CORES -m $i ./oidnBenchmark $@ --threads $(echo "$CORES" | tr ',' ' ' | wc -w) --affinity 0 > $LOG_FILE.$i.txt 2>&1; echo $? > $LOG_FILE.test-exit-status.$i.txt ) &
    done;
    wait
    for i in "${OIDN_COMPUTE_NODES[@]}";do
        # Collect error codes from sub-shells
        update_test_exit_status $(cat $LOG_FILE.test-exit-status.$i.txt 2>/dev/null)
    done;
    # Dumping aggregated values
    # Cumulative throughput, a sum of the images per second of all NUMA nodes converted back to
    # msec/image as this is the unit expected by the results parser
    for i in "${OIDN_COMPUTE_NODES[@]}";do
        awk '/msec\/image/ {sum += $3; n++} END {if (n > 0) printf "%.7f\n", 1000 * n / sum}' $LOG_FILE.$i.txt
    done | awk '{ips += $1} END {if (ips > 0) printf "NUMA.aggregate ... %.7f msec/image\n", 1000 / ips}' > $LOG_FILE
    update_test_exit_status $?
    rm $LOG_FILE.*.txt
else
    ./oidnBenchmark $@ --threads $NUM_CPU_CORES > $LOG_FILE 2>&1
    echo $? > ~/test-exit-status
fi
# clean-up pipefail
set +o pipefail
EOF
chmod +x oidn