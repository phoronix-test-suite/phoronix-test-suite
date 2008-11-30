#!/bin/sh

tar -xvf pts-sqlite-tests-1.tar.gz

tar -xvf sqlite-3.6.6.2.tar.gz
mv sqlite-3.6.6.2/ sqlite_env/

cd sqlite_env/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cat sqlite-2500-insertions.txt | ./sqlite_env/sqlite3 benchmark.db
cat sqlite-2500-insertions.txt | ./sqlite_env/sqlite3 benchmark.db
cat sqlite-2500-insertions.txt | ./sqlite_env/sqlite3 benchmark.db
cat sqlite-2500-insertions.txt | ./sqlite_env/sqlite3 benchmark.db
cat sqlite-2500-insertions.txt | ./sqlite_env/sqlite3 benchmark.db" > sqlite-inserts
chmod +x sqlite-inserts

echo "#!/bin/sh
rm -f benchmark.db
./sqlite_env/sqlite3 benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"
\$TIMER_START
./sqlite-inserts 2>&1
\$TIMER_STOP
rm -f benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
