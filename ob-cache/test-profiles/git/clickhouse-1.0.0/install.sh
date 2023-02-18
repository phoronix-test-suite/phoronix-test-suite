#!/bin/bash
git clone --recursive https://github.com/ClickHouse/ClickHouse.git clickhouse-git
mkdir build
cd build
EXTRA_FLAGS="-DCMAKE_BUILD_TYPE=Release "
if grep avx /proc/cpuinfo > /dev/null
then
	EXTRA_FLAGS="$EXTRA_FLAGS -DENABLE_AVX=ON "
fi
if grep bmi /proc/cpuinfo > /dev/null
then
	EXTRA_FLAGS="$EXTRA_FLAGS -DENABLE_BMI=ON "
fi
if grep avx2 /proc/cpuinfo > /dev/null
then
	EXTRA_FLAGS="$EXTRA_FLAGS -DENABLE_AVX2=ON "
	if grep avx512 /proc/cpuinfo > /dev/null
	then
		EXTRA_FLAGS="$EXTRA_FLAGS -DENABLE_AVX512=ON -DENABLE_AVX512_FOR_SPEC_OP=ON "
		if type accel-config > /dev/null
		then
			EXTRA_FLAGS="$EXTRA_FLAGS -DENABLE_QPL=ON "
		fi
	fi
fi
echo "Clickhouse CMake build configuration: $EXTRA_FLAGS"
cmake ../clickhouse-git $EXTRA_FLAGS
ninja
echo $? > ~/install-exit-status
cd ~
./build/programs/clickhouse server --version > ~/install-footnote 2>&1
unzip -o ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0.zip
gzip -d -k hits.tsv.gz
cp ClickBench-d9a1281ca7d2dd6c5144bd801a5ce493c0fe6fa0/clickhouse/queries.sql queries.sql
mkdir config.d
echo "<clickhouse>
<profiles>
<default>
	<allow_experimental_codecs>1</allow_experimental_codecs>
</default>
</profiles>
<compression>
<case>
	<method>deflate_qpl</method>
</case>
</compression>
</clickhouse>" > config.d/qpl.xml
CLICKHOUSE_CLIENT=build/programs/clickhouse
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
