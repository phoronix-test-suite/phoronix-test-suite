#!/bin/sh

tar -xzf redis-3.0.1.tar.gz

cd ~/redis-3.0.1/deps
make hiredis jemalloc linenoise lua

cd ~/redis-3.0.1
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-3.0.1/

./src/redis-server &
REDIS_SERVER_PID=\$!
sleep 10

./src/redis-benchmark \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID
sed \"s/\\\"/ /g\" -i \$LOG_FILE" > redis
chmod +x redis
