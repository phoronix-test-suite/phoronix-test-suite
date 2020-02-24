#!/bin/sh

if which sqlite3 >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: SQLite is not found on the system!"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
sqlite3 ~/benchmark.db  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"

cat \$TEST_EXTENDS/sqlite-insertions.txt | sqlite3 ~/benchmark.db
cat \$TEST_EXTENDS/sqlite-insertions.txt | sqlite3 ~/benchmark.db
cat \$TEST_EXTENDS/sqlite-insertions.txt | sqlite3 ~/benchmark.db

sqlite3 --version | cut -d \" \" -f 1 > ~/pts-test-version 2>/dev/null" > sqlite-benchmark
chmod +x sqlite-benchmark
