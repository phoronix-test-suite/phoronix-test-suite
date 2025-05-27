#!/bin/sh
if which podman>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Podman is not found on the system! This test profile needs a working podman installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
podman pull docker.io/cloudsuite/web-serving:db_server
podman pull docker.io/cloudsuite/web-serving:memcached_server
podman pull docker.io/cloudsuite/web-serving:web_server
podman pull docker.io/cloudsuite/web-serving:faban_client
echo $? > ~/install-exit-status
podman network create search_network
echo "#!/bin/bash
# Run data analytics benchmark
podman run --net=host --name=faban_client docker.io/cloudsuite/web-serving:faban_client 127.0.0.1 \$@ --steady=60 > pts-run.log 2>&1
echo \$? > ~/test-exit-status
cat pts-run.log | grep metric | tr '<' ' ' | tr '>' ' ' > \$LOG_FILE
podman stop faban_client
podman container rm faban_client" > cloudsuite-ws
chmod +x cloudsuite-ws
