#!/bin/sh
if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
docker pull cloudsuite/hadoop:2.10.1
docker pull cloudsuite/data-analytics:4.0
echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 
HOST_MASTER=\`hostname\`
# Start master and slaves
SLAVE_COUNT=\$1
docker run -d --net host --name master cloudsuite/data-analytics:4.0 master
for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do
   docker run -d --net host --name slave\$SLAVE_I cloudsuite/hadoop:2.10.1 slave \$HOST_MASTER
done
# Run data analytics benchmark
docker exec master benchmark > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
# Stop them
docker stop master
for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do 
   docker stop slave\$SLAVE_I
done
sleep 2
docker container rm master
for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do 
   docker container rm slave\$SLAVE_I
done" > cloudsuite-da
chmod +x cloudsuite-da
