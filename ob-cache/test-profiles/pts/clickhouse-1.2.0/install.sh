#!/bin/sh
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf clickhouse-common-static-22.12.3.5-arm64.tgz
else
	tar -xf clickhouse-common-static-22.12.3.5-amd64.tgz
fi
unzip -o ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0.zip
gzip -d -k hits.tsv.gz
cp ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0/clickhouse/queries.sql queries.sql
mkdir config.d

CLICKHOUSE_CLIENT=clickhouse-common-static-22.12.3.5/usr/bin/clickhouse
echo "#!/bin/bash
rm -rf d*
rm -rf f*
rm -rf m*
rm -rf n*
rm -rf preprocessed_configs
rm -rf s*
rm -rf tmp
rm -rf u*

TRIES=3
./$CLICKHOUSE_CLIENT server 2>/dev/null &
SERVER_PID=\$!
sleep 5
./$CLICKHOUSE_CLIENT client < ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0/clickhouse/create-tuned.sql
./$CLICKHOUSE_CLIENT client --time --query \"INSERT INTO hits FORMAT TSV\" < hits.tsv
echo \$? > ~/test-exit-status
cat queries.sql | while read query; do
    sync
    echo \"QUERY: \$query\" >> \$LOG_FILE
    for i in \$(seq 1 \$TRIES); do
    	echo -n \"Clickhouse Query Time \$i: \" >> \$LOG_FILE
    	./$CLICKHOUSE_CLIENT client --time --format=Null --max_memory_usage=100G --max_threads=\$NUM_CPU_CORES --query=\"\$query\" --progress 0 >> \$LOG_FILE 2>&1
    	retval=\$?
	if [ \$retval -ne 0 ]; then
	    echo \$retval > ~/test-exit-status
	    kill -9 \$SERVER_PID
	    sleep 3
	    exit
	fi
    done
done
kill -9 \$SERVER_PID
sleep 2
rm -rf d*
rm -rf f*
rm -rf m*
rm -rf n*
rm -rf preprocessed_configs
rm -rf s*
rm -rf tmp
rm -rf u*" > clickhouse
chmod +x clickhouse
