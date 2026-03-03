#!/bin/sh
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf clickhouse-common-static-25.8.7.3-arm64.tgz
else
	tar -xf clickhouse-common-static-25.8.7.3-amd64.tgz
fi
unzip -o ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0.zip
gzip -d -k hits.tsv.gz
cp ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0/clickhouse/queries.sql queries.sql
mkdir config.d

CLICKHOUSE_EXECUTABLE=clickhouse-common-static-25.8.7.3/usr/bin/clickhouse
echo "#!/bin/bash
# Benchmark harness expects 0, all exit codes must be 0 to pass
# Dumping and replacing non-zero code(s) to fail the run
update_test_exit_status() {
	[ \$1 -ne 0 ] && echo \$1 > ~/test-exit-status;
}

rm -rf d*
rm -rf f*
rm -rf m*
rm -rf n*
rm -rf preprocessed_configs
rm -rf s*
rm -rf tmp
rm -rf u*

TRIES=3
# Launch the server in its own process group so it doesn’t forward SIGTERM signals when shutting down
setsid ./$CLICKHOUSE_EXECUTABLE server \$CLICKHOUSE_SERVER_ARGS 2>/dev/null &
SERVER_PID=\$!
#disown \$SERVER_PID;
sleep 5
echo 0 > ~/test-exit-status
./$CLICKHOUSE_EXECUTABLE client < ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0/clickhouse/create-tuned.sql
update_test_exit_status \$?
./$CLICKHOUSE_EXECUTABLE client --time --query \"INSERT INTO hits FORMAT TSV\" < hits.tsv
update_test_exit_status \$?

# Latency Benchmarks, First iteration for every query is cold
while IFS= read -r query; do
	# sync dirty pages to disk then drop caches
	sync
	echo 3 | sudo -n tee /proc/sys/vm/drop_caches >/dev/null 2>&1 
	DIRTY_CACHE=\$?
	echo \"QUERY: \$query\" >> \$LOG_FILE
	for i in \$(seq 1 \$TRIES); do
		# if caches have not dropped succesfully we can't validate the cold iteration
		[[ \$DIRTY_CACHE -eq 0 || \$i -gt 1 ]] && echo -n \"Clickhouse Query Time \$i: \" >> \$LOG_FILE || echo -n \"Cold Query Time is invalid (can't drop cache, sudo problem?): \" >> \$LOG_FILE
		./$CLICKHOUSE_EXECUTABLE client --time --format=Null --max_memory_usage=100G --query=\"\$query\" --progress 0 >> \$LOG_FILE 2>&1
		retval=\$?; if [ \$retval -ne 0 ]; then update_test_exit_status \$retval; break; fi
	done
done < queries.sql

# Throughput Benchmark on warm data
sync
echo 3 | sudo -n tee /proc/sys/vm/drop_caches >/dev/null 2>&1 
for i in \$(seq 0 \$TRIES); do
	# Uses system ns timer, includes client(s) start-up and timer call overheads, better real-world scenario evaluation
	start_time=\$(date +%s%N)
	# Limiting  external parallelism by NUM_CPU_CORES processes, let OS and clickhouse server handle this
	while IFS= read -r query; do
		# Spinning on while if there is not enough CPUs provided, sleep command resolution is inconsistent
		while [ \$(jobs -r | wc -l) -ge \$NUM_CPU_CORES ]; do :; done
		(
			./$CLICKHOUSE_EXECUTABLE client --format=Null --max_memory_usage=100G --query=\"\$query\" --progress 0 
			# if one of queries fails just fail the full benchmark, error code does not matter
			if [ \$? -ne 0 ]; then echo 1 > ~/test-exit-status; fi 
		) &
	done < queries.sql
	for pid in \$(jobs -p); do [ \$pid -eq \$SERVER_PID ] && continue; wait \$pid; done
	stop_time=\$(date +%s%N)

	# Total execution time in nanoseconds
	nanoseconds=\$(( \$stop_time - \$start_time ))
	# Average time per query in seconds
	seconds=\$(echo \$nanoseconds | awk -v nq=\$(grep -c \"\" queries.sql) '{printf \"%1.7f\n\", ((\$1/1000000000)/nq) }')
	# skipping the cold iteration
	[ \$i -ge 1 ] && echo \"Clickhouse Throughput Time: \$seconds \" >> \$LOG_FILE
done

# Shutting the server down
./$CLICKHOUSE_EXECUTABLE client --query='SYSTEM SHUTDOWN'
sleep 5
for pid in \$(jobs -p); do if [ \$pid -eq \$SERVER_PID ]; then kill -9 \$SERVER_PID; sleep 2; fi; done

rm -rf d*
rm -rf f*
rm -rf m*
rm -rf n*
rm -rf preprocessed_configs
rm -rf s*
rm -rf tmp
rm -rf u*" > clickhouse
chmod +x clickhouse
