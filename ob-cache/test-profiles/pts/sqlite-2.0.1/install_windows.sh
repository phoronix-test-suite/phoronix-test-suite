#!/bin/sh

tar -zxvf pts-sqlite-tests-1.tar.gz
unzip -o sqlite-tools-win32-x86-3220000.zip

echo "#!/bin/sh

cat sqlite-2500-insertions.txt > sqlite-insertions.txt
./sqlite-tools-win32-x86-3220000/sqlite3.exe benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

cat sqlite-insertions.txt | ./sqlite-tools-win32-x86-3220000/sqlite3.exe benchmark.db
cat sqlite-insertions.txt | ./sqlite-tools-win32-x86-3220000/sqlite3.exe benchmark.db
cat sqlite-insertions.txt | ./sqlite-tools-win32-x86-3220000/sqlite3.exe benchmark.db" > sqlite-benchmark
chmod +x sqlite-benchmark
