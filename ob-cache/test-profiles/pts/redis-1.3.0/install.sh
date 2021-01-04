#!/bin/sh

tar -xzf redis-6.0.9.tar.gz

cd ~/redis-6.0.9/deps
make hiredis jemalloc linenoise lua

cd ~/redis-6.0.9
make MALLOC=libc -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-6.0.9

./src/redis-server &
REDIS_SERVER_PID=\$!
sleep 10

./src/redis-benchmark \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID
sed \"s/\\\"/ /g\" -i \$LOG_FILE" > redis
chmod +x redis
