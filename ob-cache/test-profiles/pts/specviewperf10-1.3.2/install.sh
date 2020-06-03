#!/bin/sh
rm -rf SPECViewperf10.1
tar -xf SPECViewperf10.tgz
tar xfj SPECViewperf101.diff.tar.bz2
mv SPECViewperf10 SPECViewperf10.0
patch -p0 < SPECViewperf101.diff
mv SPECViewperf10.0 SPECViewperf10.1
cd SPECViewperf10.1/viewperf/viewperf10.0/src/
sed -i 's/#undef BETA_VERSION/#define BETA_VERSION 0/g' viewperf.h
(cd vpaux/libtk;make clean;make)
(cd vpaux/libaux;make clean;make)

ln -s redistributable_sources/libpng/png.h png.h

if [ "$OS_TYPE" = "Solaris" ]; then
 echo 4|./Configure
elif [ "$OS_ARCH" = "x86_64" ]; then
 echo 3|./Configure
else
 echo 1|./Configure
fi
cd ~

echo "#!/bin/sh

cd SPECViewperf10.1/viewperf/viewperf10.0/

echo \"screenHeight  \$2
screenWidth  \$1\" > viewperf.config

./Run_\$3.csh
cat results/\$3-*/*result.txt > \$LOG_FILE" > specviewperf10-run
chmod +x specviewperf10-run
