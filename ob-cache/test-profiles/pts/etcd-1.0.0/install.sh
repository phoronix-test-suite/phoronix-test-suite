#!/bin/sh

rm -rf pkg
tar -xf etcd-3.5.4.tar.gz
cd etcd-3.5.4
./build.sh
go build ./tools/benchmark/

cd ~
echo "#!/bin/sh
rm -rf data
mkdir data
cd etcd-3.5.4
./bin/etcd --snapshot-count=5000 --auto-compaction-retention=10 --auto-compaction-mode=revision --data-dir \$HOME/data &
SERVER_PID=\$!
sleep 5

./benchmark \$@ > \$LOG_FILE 2>&1
kill \$SERVER_PID
sleep 2
rm -rf ~/data" > etcd
chmod +x etcd
