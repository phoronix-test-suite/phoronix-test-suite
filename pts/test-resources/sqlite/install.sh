#!/bin/sh


rm -rf sqlite-3.6.13 sqlite_env
tar -xvf pts-sqlite-tests-1.tar.gz
tar -xvf sqlite-3.6.13.tar.gz
mv sqlite-3.6.13 sqlite_env

cd sqlite_env/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh

if [ \"X\$@\" = \"X\" ]
then
	TEST_PATH=\`pwd\`
else
	TEST_PATH=\$@
fi

rm -f \$TEST_PATH/benchmark.db

cat sqlite-2500-insertions.txt > \$TEST_PATH/sqlite-insertions.txt
./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

\$TIMER_START
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_env/sqlite3 \$TEST_PATH/benchmark.db
\$TIMER_STOP

rm -f \$@/benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
