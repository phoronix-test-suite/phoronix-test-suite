#!/bin/bash
mkdir models
rm -rf openvino-github
tar -xf openvino-github-2025.3.tar.xz
cd openvino-github
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release -DENABLE_INTEL_GPU=OFF -DENABLE_INTEL_NPU=OFF -DTREAT_WARNING_AS_ERROR=OFF -DENABLE_CPPLINT=OFF ..
make -j $NUM_CPU_CORES
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ]; then
	echo $EXIT_STATUS > ~/install-exit-status
	exit 2
fi
cd ~
tar -xvf open_model_zoo-2024.6.0.tar.gz
cd open_model_zoo-2024.6.0/tools/model_tools/
pip3 install --user -r requirements.in
python3 downloader.py --name face-detection-0206 -o $HOME/models
python3 downloader.py --name age-gender-recognition-retail-0013 -o $HOME/models
python3 downloader.py --name person-detection-0303 -o $HOME/models
python3 downloader.py --name weld-porosity-detection-0001 -o $HOME/models
python3 downloader.py --name vehicle-detection-0202 -o $HOME/models
python3 downloader.py --name person-vehicle-bike-detection-2004 -o $HOME/models
python3 downloader.py --name machine-translation-nar-en-de-0002 -o $HOME/models
python3 downloader.py --name face-detection-retail-0005 -o $HOME/models
python3 downloader.py --name handwritten-english-recognition-0001 -o $HOME/models
python3 downloader.py --name road-segmentation-adas-0001 -o $HOME/models
python3 downloader.py --name person-reidentification-retail-0277 -o $HOME/models
python3 downloader.py --name noise-suppression-poconetlike-0001 -o $HOME/models
echo $? > ~/install-exit-status
cd ~
BINDIR=intel64
if [ $OS_ARCH = "aarch64" ]
then
	BINDIR=aarch64
fi
echo "#!/bin/bash
# Benchmark harness expects 0, all exit codes must be 0 to pass
# Return code might be negative so can't be accumulated
# Dumping and replacing non-zero code(s) to fail the run
update_test_exit_status() {
	[ \$1 -ne 0 ] && echo \$1 > ~/test-exit-status;
}
# setting non-zero status if any of pipeline operations fail 
set -o pipefail
echo 0 > ~/test-exit-status
if [ ! \`numactl -H >/dev/null 2>&1\` ] && [ \`numactl -H | grep -c -E \"cpus: [0-9]\"\` -gt 1 ]
then
	# Following OpenVINO documentation:
	# https://docs.openvino.ai/2025/model-server/ovms_demos_continuous_batching_scaling.html
	# * OpenVINO is the most efficient when it is bound to a single NUMA node.
	# * This applies to both - online serving or chatbots and batch or offline processing.
	# Following oneDNN documentation recommendation, oneDNN primitives are used by OpenVINO
	# https://www.intel.com/content/www/us/en/docs/onednn/developer-guide-reference/2025-2/configuring-onednn-for-benchmarking.html
	# oneDNN benchmarking recommendations:
	# * Modern CPUs may have multiple hardware threads per CPU core enabled. Such threads are usually exposed by OS as additional logical processors ...
	#   If this is the case, the recommendation is to use only one of hardware threads per core.
	# * Single NUMA domain. This setup is the recommended one.
	# So if NUMA compute nodes are available then 
	# * Running 1 benchmark instance per NUMA compute node.
	# * Providing aggregated values to the benchmark harness. See below comments for details.
	COMPUTE_NODE_COUNT=\`numactl -H | grep -c -E \"cpus: [0-9]\"\`
	update_test_exit_status \$?
	COMPUTE_NODES=\`numactl -H | grep -E \"cpus: [0-9]\" | awk '{print \$2}' | tr '\n' ' '\`
	update_test_exit_status \$?
	for i in \$COMPUTE_NODES;do
		# Get physical cores on the compute node
		CORES=\`lscpu -e=CPU,CORE,NODE | awk -v node=\$i '\$3==node && !seen[\$2]++ {printf \"%s,\",\$1}' | sed 's/,\$//'\`
		# The function is not thread-safe, running sequentially 
		update_test_exit_status \$?
		( numactl -C \$CORES ./openvino-github/bin/$BINDIR/Release/benchmark_app \$@ > \$LOG_FILE.\$i.txt; echo $? > \$LOG_FILE.test-exit-status.\$i.txt ) &
	done;
	wait
	rm \$LOG_FILE.sum.txt 2>/dev/null
	for i in \$COMPUTE_NODES;do
		# Dump logs from instances into one file
		cat \$LOG_FILE.\$i.txt >> \$LOG_FILE.sum.txt
		# Collect error codes from sub-shells
		update_test_exit_status \$(cat \$LOG_FILE.test-exit-status.\$i.txt 2>/dev/null)
	done;
	# Dumping aggregated values
	echo \"[ INFO ] Latency:\" >> \$LOG_FILE
	update_test_exit_status \$?
	# Average across all averages
	echo \"[ INFO ]    Average:          \`grep \"\[ INFO \]    Average:\" \$LOG_FILE.sum.txt  | awk -v n=\$COMPUTE_NODE_COUNT '{s+=\$5}END{print s/n}'\` ms\" >> \$LOG_FILE
	update_test_exit_status \$?
	# Min across all
	echo \"[ INFO ]    Min:              \`grep \"\[ INFO \]    Min:\" \$LOG_FILE.sum.txt  | awk 's==\"\" || \$5 < s {s=\$5}END{print s}'\` ms\" >> \$LOG_FILE
	update_test_exit_status \$?
	# Max across all
	echo \"[ INFO ]    Max:              \`grep \"\[ INFO \]    Max:\" \$LOG_FILE.sum.txt  | awk 's==\"\" || \$5 > s {s=\$5}END{print s}'\` ms\" >> \$LOG_FILE
	update_test_exit_status \$?
	# Cumulative throughput
	echo \"[ INFO ] Throughput: \`grep \"\[ INFO \] Throughput:\" \$LOG_FILE.sum.txt  | awk '{s+=\$5}END{print s}'\` FPS\" >> \$LOG_FILE
	update_test_exit_status \$?
	echo \"\"
	rm \$LOG_FILE.*.txt
else
	./openvino-github/bin/$BINDIR/Release/benchmark_app \$@ > \$LOG_FILE
	update_test_exit_status \$?
fi
# clean-up pipefail 
set +o pipefail
" > openvino
chmod +x openvino
