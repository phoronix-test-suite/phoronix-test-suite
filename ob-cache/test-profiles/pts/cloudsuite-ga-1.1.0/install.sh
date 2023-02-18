#!/bin/sh
if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
docker pull cloudsuite/graph-analytics:4.0
docker pull cloudsuite/twitter-dataset-graph:4.0
echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 
# Start
docker create --name twitter-data cloudsuite/twitter-dataset-graph:4.0
# Run benchmark
MEMORY_LIMIT=\`echo \"scale=0;\$SYS_MEMORY / 1024 * 0.91\" |bc -l | cut -d'.' -f1\`
echo \"Memory Limit is \${MEMORY_LIMIT}g\"
docker run --rm --volumes-from twitter-data -e WORKLOAD_NAME=\$1 cloudsuite/graph-analytics:4.0 --driver-memory \${MEMORY_LIMIT}g --executor-memory \${MEMORY_LIMIT}g > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
# Stop them
docker stop twitter-data
docker container rm twitter-data" > cloudsuite-ga
chmod +x cloudsuite-ga
