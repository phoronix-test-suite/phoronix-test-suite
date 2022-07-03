#!/bin/sh

rm -rf src
rm -rf .cache
export GOPATH=$HOME
tar -xf influxdb-1.8.2_linux_amd64.tar.gz
go get github.com/influxdata/inch/cmd/inch
if [ $? -ne 0 ]
then
   # Go 1.17+ path
   go install github.com/influxdata/inch/cmd/inch@latest
fi
echo $? > ~/install-exit-status

echo "#!/bin/sh
cd influxdb-1.8.2-1/usr/bin/
./influxd &
INFLUX_SERVER_PID=\$!
sleep 5

cd ~
./bin/inch -v -c 256 -b 10000 -t 2,5000,1 -p 5000 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status


kill \$INFLUX_SERVER_PID
sleep 1
rm -rf ~/.influxdb
rm -rf \$DEBUG_REAL_HOME/.influxdb" > influxdb
chmod +x influxdb
