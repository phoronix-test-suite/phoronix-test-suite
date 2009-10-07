#!/bin/sh
set
SDK=ati-stream-sdk-v2.0-beta3-lnx64

tar -xvf ${SDK}.tgz
cd ${SDK}
make -j $NUM_CPU_JOBS

cd ..
echo "#!/bin/bash
TYPE=\${PTS_TEST_ARGUMENTS%% *}
ARG=\${PTS_TEST_ARGUMENTS#* }
APP=\${ARG%%,*}
if [ "\$TYPE" == "cpu" ] ; then
export LD_LIBRARY_PATH=`pwd`/${SDK}/lib/\$OS_ARCH
fi
cd ./${SDK}/samples/opencl/bin/\$OS_ARCH
./\${APP} --device \$TYPE 2>&1 | tail -n 2 > \$LOG_FILE
echo \$? > ~/test-exit-status
cat \$LOG_FILE
" > opencl-ati

chmod +x opencl-ati
