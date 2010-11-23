#!/bin/sh

tar -zxvf pts-sqlite-tests-1.tar.gz
tar -zxvf sqlite-amalgamation-3.7.3.tar.gz
mkdir sqlite_/

cd sqlite-3.7.3/
./configure --prefix=$HOME/sqlite_/
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf sqlite-3.7.3/
rm -rf sqlite_/lib/

echo "#!/bin/sh

if [ \"X\$@\" = \"X\" ]
then
	TEST_PATH=\`pwd\`
else
	TEST_PATH=\$@
fi

cat sqlite-2500-insertions.txt > \$TEST_PATH/sqlite-insertions.txt
./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db
cat \$TEST_PATH/sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$TEST_PATH/benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
