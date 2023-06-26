#!/bin/sh
tar -zxvf pts-sqlite-tests-1.tar.gz
tar -zxvf sqlite-autoconf-3410200.tar.gz
mkdir sqlite_/
cd sqlite-autoconf-3410200/
./configure --prefix=$HOME/sqlite_/
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf sqlite-autoconf-3410200/
echo "#!/bin/bash
thread_num=\$1
if ! [[ \"\$thread_num\" =~ ^[0-9]+$ ]]
    then
        thread_num=1
fi
cat sqlite-2500-insertions.txt > sqlite-insertions.txt
do_test() {
    DB=benchmark-\$1.db
    ./sqlite_/bin/sqlite3 \$DB  \"CREATE TABLE pts1 ('I' SMALLINT NOT NULL, 'DT' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 'F1' VARCHAR(4) NOT NULL, 'F2' VARCHAR(16) NOT NULL);\"
    cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$DB
    cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$DB
    cat sqlite-insertions.txt | ./sqlite_/bin/sqlite3 \$DB
}
pids=\"\"
for i in \$(seq 1 \$thread_num)
do
    do_test \$i &
    pids=\"\$pids \$!\"
done
wait \$pids" > sqlite-benchmark
chmod +x sqlite-benchmark
