#!/bin/sh

tar -zxvf pts-sqlite-tests-1.tar.gz
tar -zxvf sqlite-autoconf-3220000.tar.gz
mkdir sqlite_/

cd sqlite-autoconf-3220000/
./configure --prefix=$HOME/sqlite_/
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf sqlite-autoconf-3220000/

echo "#!/bin/sh

cat sqlite-2500-insertions.txt > sqlite-insertions.txt
./sqlite_/bin/sqlite3 benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 benchmark.db
cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 benchmark.db
cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
