#!/bin/sh
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa
7z x Beauty_3840x2160_120fps_420_10bit_YUV_RAW.7z -aoa
tar -xf SVT-AV1-v4.0.0.tar.bz2
cd SVT-AV1-v4.0.0/Build/linux
EXTRA_FLAGS="--enable-lto "
if [ $OS_TYPE = "Linux" ]
then
    if grep avx512 /proc/cpuinfo > /dev/null
    then
	EXTRA_FLAGS="$EXTRA_FLAGS --enable-avx512"
    fi
fi
./build.sh release $EXTRA_FLAGS
echo $? > ~/install-exit-status
cd ~
cat << 'EOF' > svt-av1
#!/bin/bash
# setting non-zero status if any of pipeline operations fail
set -o pipefail
# Benchmark harness expects 0, all exit codes must be 0 to pass
# Dumping and replacing non-zero code(s) to fail the run
update_test_exit_status() {
    [ $1 -ne 0 ] && echo $1 > ~/test-exit-status;
}
# Debug variables to run without PTS
LOG_FILE=${LOG_FILE:="`pwd`/svt-av1.log"}

# Determine NUMA compute nodes and their count
# if numactl is not available, this will fallback to a single compute node
SVT_COMPUTE_NODES=(${SVT_COMPUTE_NODES:=`numactl -H | grep -E "cpus: [0-9]" | awk '{print $2}' | tr '\n' ' '`})
SVT_COMPUTE_NODE_COUNT=${#SVT_COMPUTE_NODES[@]}

if [ $SVT_COMPUTE_NODE_COUNT -gt 1 ]
then
    # SVT-AV1 is not NUMA aware, so if NUMA compute nodes are available then encoding
    # one stream per NUMA node, several streams in parallel maximizing memory locality:
    # * Running 1 benchmark instance per NUMA compute node, using all its logical CPUs.
    # * Providing aggregated values to the benchmark harness. See below comments for details.
    echo $? > ~/test-exit-status
    update_test_exit_status $?
    for i in "${SVT_COMPUTE_NODES[@]}";do
        ( numactl -N $i -m $i ./SVT-AV1-v4.0.0/Bin/Release/SvtAv1EncApp $@ > $LOG_FILE.$i.txt 2>&1; echo $? > $LOG_FILE.test-exit-status.$i.txt ) &
    done;
    wait
    for i in "${SVT_COMPUTE_NODES[@]}";do
        # Collect error codes from sub-shells
        update_test_exit_status $(cat $LOG_FILE.test-exit-status.$i.txt 2>/dev/null)
    done;
    # Dumping aggregated values
    # Cumulative throughput, a sum of the fps of all NUMA nodes
    for i in "${SVT_COMPUTE_NODES[@]}";do
        awk '/^Average Speed:/ {print $3}' $LOG_FILE.$i.txt
    done | awk '{fps += $1} END {if (fps > 0) printf "Average Speed:\t\t%.3f fps\n", fps}' > $LOG_FILE
    update_test_exit_status $?
    rm $LOG_FILE.*.txt
else
    ./SVT-AV1-v4.0.0/Bin/Release/SvtAv1EncApp $@ > $LOG_FILE 2>&1
    echo $? > ~/test-exit-status
fi
# clean-up pipefail
set +o pipefail
EOF
chmod +x svt-av1
