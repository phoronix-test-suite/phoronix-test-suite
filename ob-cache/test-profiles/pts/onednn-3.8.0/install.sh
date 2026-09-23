#!/bin/bash
tar -xf oneDNN-3.11.1.tar.gz
cd oneDNN-3.11.1
mkdir build 
cd build 
CFLAGS="-O3 -march=native $CFLAGS" CXXFLAGS="-O3 -march=native $CXXFLAGS" cmake -DCMAKE_BUILD_TYPE=Release -DDNNL_GPU_RUNTIME=OCL MKLDNN_ARCH_OPT_FLAGS="-O3 -march=native $CFLAGS" $CMAKE_OPTIONS ..
make -j $NUM_CPU_CORES
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ]; then
	# For architectures not supporting -march=native compiler option...
	CFLAGS="-O3 $CFLAGS" CXXFLAGS="-O3 $CXXFLAGS" cmake -DCMAKE_BUILD_TYPE=Release -DDNNL_GPU_RUNTIME=OCL MKLDNN_ARCH_OPT_FLAGS="-O3 $CFLAGS" $CMAKE_OPTIONS ..
	make -j $NUM_CPU_CORES
	EXIT_STATUS=$?
fi
echo $EXIT_STATUS > ~/install-exit-status
cd ~
cat <<'EOF' > onednn
#!/bin/bash
# setting non-zero status if any of pipeline operations fail 
set -o pipefail
# Benchmark harness expects 0, all exit codes must be 0 to pass
# Dumping and replacing non-zero code(s) to fail the run
update_test_exit_status() {
	[ $1 -ne 0 ] && echo $1 > ~/test-exit-status;
}
# Debug variables to run without PTS
LOG_FILE=${LOG_FILE:="`pwd`/onednn.log"}
NUM_CPU_PHYSICAL_CORES=${NUM_CPU_PHYSICAL_CORES:-`lscpu | awk '/^Core\(s\) per socket:/ {cores=$4} /^Socket\(s\):/ {sockets=$2} END {print cores*sockets}'`}
# oneDNN threading setup
export DNNL_CPU_RUNTIME=OMP
export OMP_PLACES=cores
export OMP_PROC_BIND=close
export OMP_WAIT_POLICY=ACTIVE 
# Here and below OMP_NUM_THREADS should match physical cores used by a process
# otherwise OpenMP will pin several threads to a core which will lead to a huge oversubscription 
export OMP_NUM_THREADS=$NUM_CPU_PHYSICAL_CORES
cd oneDNN-3.11.1/build/tests/benchdnn
ONEDNN_COMPUTE_NODES=(${ONEDNN_COMPUTE_NODES:=`numactl -H | grep -E "cpus: [0-9]" | awk '{print $2}' | tr '\n' ' '`})
ONEDNN_COMPUTE_NODE_COUNT=${#ONEDNN_COMPUTE_NODES[@]}

if [ "$4" == "--engine=cpu" ] && [ $ONEDNN_COMPUTE_NODE_COUNT -gt 1 ]
then
	# Following oneDNN documentation recommendation
	# https://www.intel.com/content/www/us/en/docs/onednn/developer-guide-reference/2025-2/configuring-onednn-for-benchmarking.html
	# oneDNN benchmarking recommendations:
	# * Modern CPUs may have multiple hardware threads per CPU core enabled. Such threads are usually exposed by OS as additional logical processors ...
	#   If this is the case, the recommendation is to use only one of hardware threads per core.
	# * Single NUMA domain. This setup is the recommended one.
	# So if NUMA compute nodes are available then 
	# * Running 1 benchmark instance per NUMA compute node.
	# * Providing aggregated values to the benchmark harness. See below comments for details.
	echo $? > ~/test-exit-status
	update_test_exit_status $?
	for i in "${ONEDNN_COMPUTE_NODES[@]}";do
		# Get physical cores on the compute node
		CORES=`lscpu -e=CPU,CORE,NODE | awk -v node=$i '$3==node && !seen[$2]++ {printf "%s,",$1}' | sed 's/,$//'`
		# The function is not thread-safe, running sequentially 
		update_test_exit_status $?
		( OMP_NUM_THREADS=$(echo "$CORES" | tr ',' ' ' |  wc -w ) numactl -C $CORES ./benchdnn --mode=p $1 --perf-template=GFLOPS:%-Gflops% $3 $2 $4 2>&1 | grep -oP 'GFLOPS:\K[0-9.]+' | awk '{sum += $1; n++; print $1} END {printf "Result: %.7e GFLOPS\n", sum/n}' > $LOG_FILE.$i.txt; echo $? > $LOG_FILE.test-exit-status.$i.txt ) &
	done;
	wait
	rm $LOG_FILE.sum.txt 2>/dev/null
	for i in "${ONEDNN_COMPUTE_NODES[@]}";do
		# Dump logs from instances into one file
		cat $LOG_FILE.$i.txt >> $LOG_FILE.sum.txt
		# Collect error codes from sub-shells
		update_test_exit_status $(cat $LOG_FILE.test-exit-status.$i.txt 2>/dev/null)
	done;
	# Dumping aggregated values
	# Cumulative throughput, a average of Gflops of all operations in the requested batch accumulated across all NUMA nodes
	grep "Result:" $LOG_FILE.sum.txt | awk '{s+=$2}END{printf "\nResult: %.7e GFLOPS\n", s}' >> $LOG_FILE
	update_test_exit_status $?
	echo "" >> $LOG_FILE
	rm $LOG_FILE.*.txt
else
	# Result is an average of Gflops of all operations in the requested batch
	./benchdnn --mode=p $1 --perf-template=GFLOPS:%-Gflops% $3 $2 $4 2>&1 | grep -oP 'GFLOPS:\K[0-9.]+' | awk '{sum += $1; n++; print $1} END {printf "\nResult: %.7e GFLOPS\n", sum/n}'  > $LOG_FILE 2>&1
	echo $? > ~/test-exit-status
fi
# clean-up pipefail 
set +o pipefail
EOF
chmod +x onednn
