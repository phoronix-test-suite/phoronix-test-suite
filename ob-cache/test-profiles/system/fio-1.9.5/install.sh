#!/bin/sh

if which fio>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: fio is not found on the system! This test profile needs a working fio installation in the PATH."
	echo 2 > ~/install-exit-status
fi


echo "#!/bin/sh

if [ ! \"X\$6\" = \"X\" ]
then
DIRECTORY_TO_TEST=\"directory=\$6\"
fi

echo \"[global]
rw=\$1
ioengine=\$2
iodepth=64
size=1g
direct=\$4
buffered=\$3
startdelay=5
ramp_time=5
runtime=20
time_based\" > test.fio

if [ \"\${OS_TYPE}\" != \"BSD\" ]; then
	echo \"disk_util=0\" >> test.fio
fi

echo \"clat_percentiles=0
disable_lat=1
disable_clat=1
disable_slat=1
filename=fiofile
\$DIRECTORY_TO_TEST

[test]
name=test
bs=\$5
stonewall\" >> test.fio

fio test.fio 2>&1 > \$LOG_FILE
fio --version | cut -d \"-\" -f 2 > ~/pts-test-version 2>/dev/null " > fio-run
chmod +x fio-run
