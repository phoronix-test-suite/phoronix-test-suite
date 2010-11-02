#!/bin/sh

tar -xvf SPECViewperf10.tgz
cd SPECViewperf10/viewperf/viewperf10.0/src/
(cd vpaux/libtk;make clean;make)
(cd vpaux/libaux;make clean;make)
if [ "$OS_TYPE" = "Solaris" ]; then
 echo 4|./Configure
elif [ "$OS_ARCH" = "x86_64" ]; then
 echo 3|./Configure
else
 echo 1|./Configure
fi
cd $1

echo "#!/bin/sh

cd SPECViewperf10/viewperf/viewperf10.0/

echo \"screenHeight  \$2
screenWidth  \$1\" > viewperf.config

./Run_\$3.csh
cat results/\$3-*/*result.txt > \$LOG_FILE" > specviewperf10
chmod +x specviewperf10
