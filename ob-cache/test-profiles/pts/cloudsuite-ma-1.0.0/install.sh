#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

docker pull cloudsuite/in-memory-analytics
docker pull cloudsuite/movielens-dataset
echo $? > ~/install-exit-status


echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 

# Start
docker create --name data cloudsuite/movielens-dataset

# Run in-memory analytics benchmark
docker run --rm --volumes-from data cloudsuite/in-memory-analytics /data/ml-latest-small /data/myratings.csv \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
docker stop data
docker container rm data
" > cloudsuite-ma
chmod +x cloudsuite-ma
