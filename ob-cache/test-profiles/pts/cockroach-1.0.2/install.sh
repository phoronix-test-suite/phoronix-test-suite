#!/bin/sh
rm -rf cockroach-build
if [ $OS_ARCH = "aarch64" ]
then
	tar -xvf cockroach-v22.2.0.linux-3.7.10-gnu-aarch64.tgz
	mv cockroach-v22.2.0.linux-3.7.10-gnu-aarch64 cockroach-build
else
	tar -xf cockroach-v22.2.0.linux-amd64.tgz
	mv cockroach-v22.2.0.linux-amd64/ cockroach-build
fi
echo "#!/bin/sh
cd cockroach-build
./cockroach start-single-node --cache .25 --insecure > \$LOG_FILE 2>&1 &
COCKROACH_PID=\$!
sleep 5

# Run test
./cockroach workload run \$@ >> \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

kill \$COCKROACH_PID
sleep 1
rm -rf cockroach-data/" > cockroach
chmod +x cockroach
