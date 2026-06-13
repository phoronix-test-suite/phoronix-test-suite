#!/bin/bash
# Start master and slaves
podman run -dt --net=host --name=database_server docker.io/cloudsuite/web-serving:db_server
# database_server needs time to download payload from Internet...
while ! podman logs database_server | grep -q "Starting MariaDB database server";
do
    sleep 5
    echo "waiting on db server to start..."
done
podman run -dt --net=host --name=memcache_server docker.io/cloudsuite/web-serving:memcached_server
IP_ADDR=`hostname -I | cut -d" " -f1`
podman run -dt --net=host --name=web_server docker.io/cloudsuite/web-serving:web_server /etc/bootstrap.sh http $IP_ADDR $IP_ADDR $IP_ADDR 800
