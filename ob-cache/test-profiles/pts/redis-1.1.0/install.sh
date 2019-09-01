#!/bin/sh

tar -xzf redis-4.0.8.tar.gz

cd ~/redis-4.0.8/deps
make hiredis jemalloc linenoise lua

cd ~/redis-4.0.8
make MALLOC=libc -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-4.0.8/

./src/redis-server &
REDIS_SERVER_PID=\$!
sleep 10

./src/redis-benchmark \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID
sed \"s/\\\"/ /g\" -i \$LOG_FILE" > redis
chmod +x redis
