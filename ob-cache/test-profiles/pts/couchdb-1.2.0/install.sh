#!/bin/bash
tar -xf apache-couchdb-benchbulk-1.tar.xz
tar -xf apache-couchdb-3.2.2.tar.gz
cd apache-couchdb-3.2.2
MOZJS_VERSION=`ls /usr/include/mozjs-*/jsapi.h | grep "mozjs-" | sort | head -n1 | cut -d '-' -f2 | cut -d '/' -f1`
case $MOZJS_VERSION in
    ''|*[!0-9]*) MOZJS_VERSION=78 ;;
    *) echo "MOZJS_VERSION = $MOZJS_VERSION" ;;
esac

./configure --disable-docs --disable-fauxton --skip-deps --spidermonkey-version $MOZJS_VERSION 
make -j $NUM_CPU_CORES
make release
echo $? > ~/install-exit-status

echo "admin = couchPTStest" >> rel/couchdb/etc/local.ini

cd ~
echo "#!/bin/sh
cd apache-couchdb-3.2.2/rel/couchdb
./bin/couchdb &
echo \$? > ~/test-exit-status
COUCH_SERVER_PID=\$!
sleep 5
cd ~
bash benchbulk.sh \$@ > \$LOG_FILE 2>&1
kill \$COUCH_SERVER_PID
sleep 1" > couchdb
chmod +x couchdb
