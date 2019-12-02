#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

docker pull cloudsuite/media-streaming:dataset
docker pull cloudsuite/media-streaming:server
docker pull cloudsuite/media-streaming:client
echo $? > ~/install-exit-status


echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start
docker network create streaming_network

docker create --name streaming_dataset cloudsuite/media-streaming:dataset
docker run -d --name streaming_server --volumes-from streaming_dataset --net streaming_network cloudsuite/media-streaming:server

# Run in-memory analytics benchmark
docker run -t --name=streaming_client -v /path/to/output:/output --volumes-from streaming_dataset --net streaming_network cloudsuite/media-streaming:client streaming_server \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
docker stop streaming_dataset
docker stop streaming_server
docker stop streaming_client
docker container rm streaming_dataset
docker container rm streaming_server
docker container rm streaming_client

" > cloudsuite-ms
chmod +x cloudsuite-ms
