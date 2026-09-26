#!/bin/sh

if which podman>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: podman is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

podman pull docker.io/cloudsuite/graph-analytics
podman pull docker.io/cloudsuite/twitter-dataset-graph
echo $? > ~/install-exit-status


echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start
podman run --privileged --name data-ga docker.io/cloudsuite/twitter-dataset-graph

# Run benchmark
podman run --privileged --net host --rm --volumes-from data-ga -e WORKLOAD_NAME=pr docker.io/cloudsuite/graph-analytics --driver-memory 16g --executor-memory 16g \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
podman stop data-ga
podman container rm data-ga

" > cloudsuite-ga
chmod +x cloudsuite-ga
