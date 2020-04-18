#!/bin/sh

tar -xzf fio-3.18.tar.gz
cd fio-3.18/
./configure
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd fio-3.18/
if [ \"X\$6\" = \"X\" ]
then
	DIRECTORY_TO_TEST=\"fiofile\"
else
	DIRECTORY_TO_TEST=\"\$6/fiofile\"
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
if [ \"\${OPERATING_SYSTEM}\" != \"freebsd\" ]
then
	echo \"disk_util=0\" >> test.fio
fi
echo \"clat_percentiles=0
disable_lat=1
disable_clat=1
disable_slat=1
filename=\$DIRECTORY_TO_TEST
[test]
name=test
bs=\$5
stonewall\" >> test.fio
./fio test.fio 2>&1 > \$LOG_FILE" > fio-run
chmod +x fio-run
