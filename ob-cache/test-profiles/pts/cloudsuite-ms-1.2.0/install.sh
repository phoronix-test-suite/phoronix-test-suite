#!/bin/sh
if which podman>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Podman is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

podman pull cloudsuite/media-streaming:dataset
podman pull cloudsuite/media-streaming:server
docker pull cloudsuite/media-streaming:client
echo $? > ~/install-exit-status

echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start
podman network create streaming_network

podman run --name streaming_dataset cloudsuite/media-streaming:dataset
podman run -d --name streaming_server --volumes-from streaming_dataset --net streaming_network cloudsuite3/media-streaming:server

# Run media streaming benchmark
podman run -t --name=streaming_client -v /path/to/output:/output --volumes-from streaming_dataset --net streaming_network cloudsuite3/media-streaming:client streaming_server \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
podman stop streaming_dataset
podman stop streaming_server
podman stop streaming_client
podman container rm streaming_dataset
podman container rm streaming_server
podman container rm streaming_client" > cloudsuite-ms
chmod +x cloudsuite-ms
