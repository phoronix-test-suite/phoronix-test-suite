#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

docker pull cloudsuite/web-serving:db_server
docker pull cloudsuite/web-serving:memcached_server
docker pull cloudsuite/web-serving:web_server
docker pull cloudsuite/web-serving:faban_client
echo $? > ~/install-exit-status

docker network create search_network

echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start master and slaves
docker run -dt --net=host --name=mysql_server cloudsuite/web-serving:db_server 127.0.0.1
docker run -dt --net=host --name=memcache_server cloudsuite/web-serving:memcached_server
docker run -dt --net=host --name=web_server cloudsuite/web-serving:web_server /etc/bootstrap.sh 127.0.0.1 127.0.0.1 80

# Run data analytics benchmark
docker run --net=host --name=faban_client cloudsuite/web-serving:faban_client 127.0.0.1 7 \$@ | tr '<' ' ' | tr '>' ' ' > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
docker stop mysql_server
docker stop memcache_server
docker stop web_server
docker stop faban_client

docker container rm mysql_server
docker container rm memcache_server
docker container rm web_server
docker container rm faban_client

" > cloudsuite-ws
chmod +x cloudsuite-ws
