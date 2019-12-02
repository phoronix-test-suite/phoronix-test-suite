#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

docker pull cloudsuite/graph-analytics
docker pull cloudsuite/twitter-dataset-graph
echo $? > ~/install-exit-status


echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start
docker create --name data-ga cloudsuite/twitter-dataset-graph

# Run benchmark
docker run --rm --volumes-from data-ga cloudsuite/graph-analytics --driver-memory 16g --executor-memory 16g \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
docker stop data-ga
docker container rm data-ga

" > cloudsuite-ga
chmod +x cloudsuite-ga
