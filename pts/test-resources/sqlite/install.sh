#!/bin/sh

tar -xvf pts-sqlite-tests-1.tar.gz
tar -xvf sqlite-3.6.13.tar.gz
mkdir sqlite_/

cd sqlite-3.6.13/
./configure --prefix=$HOME/sqlite_/
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf sqlite-3.6.13/
rm -rf sqlite_/lib/

echo "#!/bin/sh

if [ \"X\$@\" = \"X\" ]
then
	TEST_PATH=\`pwd\`
else
	TEST_PATH=\$@
fi

rm -f \$TEST_PATH/benchmark.db

cat sqlite-2500-insertions.txt > \$TEST_PATH/sqlite-insertions.txt
./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

\$TIMER_START
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
\$TIMER_STOP

rm -f \$TEST_PATH/benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
