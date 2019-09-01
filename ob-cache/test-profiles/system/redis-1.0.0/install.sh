#!/bin/sh
if which redis-server>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Redis server is not found on the system!"
	echo 2 > ~/install-exit-status
fi
echo "#!/bin/sh
redis-server &
REDIS_SERVER_PID=\$!
sleep 15
redis-benchmark \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID
redis-server --version | cut -d \" \" -f 3 |  tr -d 'v=' > ~/pts-test-version 2>/dev/null
sed \"s/\\\"/ /g\" -i \$LOG_FILE" > redis
chmod +x redis
