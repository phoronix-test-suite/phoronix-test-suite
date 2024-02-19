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
# Run data analytics benchmark
docker run --net=host --name=faban_client cloudsuite/web-serving:faban_client 127.0.0.1 \$@ --steady=60 > pts-run.log 2>&1
echo \$? > ~/test-exit-status
cat pts-run.log | grep metric | tr '<' ' ' | tr '>' ' ' > \$LOG_FILE
docker stop faban_client
docker container rm faban_client" > cloudsuite-ws
chmod +x cloudsuite-ws
