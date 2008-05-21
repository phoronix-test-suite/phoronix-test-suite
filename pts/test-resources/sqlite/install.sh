#!/bin/sh

cd $1

tar -xvf pts-sqlite-tests-1.tar.gz
tar -xvf sqlite-3.5.9.tar.gz

cd sqlite-3.5.9/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cat sqlite-2500-insertions.txt | ./sqlite-3.5.9/sqlite3 benchmark.db" > sqlite-inserts
chmod +x sqlite-inserts

echo "#!/bin/sh
rm -f benchmark.db
./sqlite-3.5.9/sqlite3 benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL, PRIMARY KEY ('I'), UNIQUE ('I'));\"
time -f \"SQLite Time: %e Seconds\" ./sqlite-inserts 2>&1
rm -f benchmark.db" > sqlite
chmod +x sqlite
