#!/bin/sh
if which podman>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: podman is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
echo step1

echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 
HOST_MASTER=$(hostname -I | awk '{print $1}')

echo pulling cloudsuite/data-analytics
podman pull docker.io/cloudsuite/data-analytics

echo pulling cloudsuite/wikimedia-pages-dataset
podman pull docker.io/cloudsuite/wikimedia-pages-dataset

echo running cloudsuite/wikimedia-pages-dataset
podman run --privileged --name wikimedia-dataset docker.io/cloudsuite/wikimedia-pages-dataset

# Start master and slaves
SLAVE_COUNT=\$1

echo running master
podman run -d --privileged --net host --volumes-from wikimedia-dataset --name master docker.io/cloudsuite/data-analytics --master

for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do
   echo running slave\$SLAVE_I
   podman run --privileged -d --net host --name slave\$SLAVE_I docker.io/cloudsuite/data-analytics --slave --master-ip=\$HOST_MASTER
done

# Run data analytics benchmark
podman exec master benchmark > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

# Stop them
podman stop master
for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do 
   podman stop slave\$SLAVE_I
done

sleep 2
podman container rm master

for (( SLAVE_I=1; SLAVE_I<=\$SLAVE_COUNT; SLAVE_I++ ))
do 
   podman container rm slave\$SLAVE_I
done

podman rm wikimedia-dataset
" > cloudsuite-da
chmod +x cloudsuite-da
