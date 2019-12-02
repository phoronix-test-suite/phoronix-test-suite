#!/bin/sh

unzip -o fio-3.16-x64.zip
mv fio-3.16-x64 fio-3.16

echo "#!/bin/sh
cd fio-3.16/

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
time_based
clat_percentiles=0
disable_lat=1
disable_clat=1
disable_slat=1
filename=fiofile
\$DIRECTORY_TO_TEST

[test]
name=test
bs=\$5
stonewall\" > test.fio

./fio.exe test.fio > \$LOG_FILE" > fio-run
chmod +x fio-run
