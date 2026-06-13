#!/bin/bash
docker stop database_server
docker stop memcache_server
docker stop web_server
docker stop faban_client

docker container rm database_server
docker container rm memcache_server
docker container rm web_server
docker container rm faban_client
