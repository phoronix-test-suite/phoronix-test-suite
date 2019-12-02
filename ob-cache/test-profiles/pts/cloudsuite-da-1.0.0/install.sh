#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

docker pull cloudsuite/hadoop
docker pull cloudsuite/data-analytics
echo $? > ~/install-exit-status

docker network create hadoop-net

echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start master and slaves
docker run -d --net hadoop-net --name master --hostname master cloudsuite/data-analytics master
docker run -d --net hadoop-net --name slave01 --hostname slave01 cloudsuite/hadoop slave
docker run -d --net hadoop-net --name slave02 --hostname slave02 cloudsuite/hadoop slave
docker run -d --net hadoop-net --name slave03 --hostname slave03 cloudsuite/hadoop slave
docker run -d --net hadoop-net --name slave04 --hostname slave04 cloudsuite/hadoop slave
docker run -d --net hadoop-net --name slave05 --hostname slave05 cloudsuite/hadoop slave
docker run -d --net hadoop-net --name slave06 --hostname slave06 cloudsuite/hadoop slave

# Run data analytics benchmark
docker exec master benchmark > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
docker stop master
docker stop slave01
docker stop slave02
docker stop slave03
docker stop slave04
docker stop slave05
docker stop slave06

docker container rm master
docker container rm slave01
docker container rm slave02
docker container rm slave03
docker container rm slave04
docker container rm slave05
docker container rm slave06

" > cloudsuite-da
chmod +x cloudsuite-da
