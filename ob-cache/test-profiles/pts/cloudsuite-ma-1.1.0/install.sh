#!/bin/sh
if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
docker pull cloudsuite/in-memory-analytics:4.0
docker pull cloudsuite/movielens-dataset:4.0
echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME
# Start
docker create --name movielens-data cloudsuite/movielens-dataset:4.0
# Run in-memory analytics benchmark
MEMORY_LIMIT=\`echo \"scale=0;\$SYS_MEMORY / 1024 * 0.91\" |bc -l | cut -d'.' -f1\`
echo \"Memory Limit is \${MEMORY_LIMIT}g\"
docker run --rm --volumes-from movielens-data cloudsuite/in-memory-analytics:4.0 \"\$1\" /data/myratings.csv --driver-memory \${MEMORY_LIMIT}g --executor-memory \${MEMORY_LIMIT}g > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
# Stop them
docker stop movielens-data
docker container rm movielens-data
" > cloudsuite-ma
chmod +x cloudsuite-ma
