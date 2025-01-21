#!/bin/sh
if which podman>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Podman is not found on the system! This test profile needs a working podman installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
podman pull docker.io/cloudsuite3/in-memory-analytics
podman pull docker.io/cloudsuite3/movielens-dataset
echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME
# Start
podman run --privileged --name movielens-data docker.io/cloudsuite3/movielens-dataset
# Run in-memory analytics benchmark
MEMORY_LIMIT=\`echo \"scale=0;\$SYS_MEMORY / 1024 * 0.91\" |bc -l | cut -d'.' -f1\`
echo \"Memory Limit is \${MEMORY_LIMIT}g\"
podman run --privileged --rm --volumes-from movielens-data docker.io/cloudsuite3/in-memory-analytics \"\$1\" /data/myratings.csv --driver-memory \${MEMORY_LIMIT}g --executor-memory \${MEMORY_LIMIT}g > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
# Stop them
podman stop movielens-data
podman container rm movielens-data
" > cloudsuite-ma
chmod +x cloudsuite-ma
