#!/bin/sh

tar -zxvf askap-benchmarks-20151110.tar.gz
cd askap-benchmarks/tConvolveCuda

make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd askap-benchmarks/tConvolveCuda
./tConvolveCuda > \$LOG_FILE
echo \$? > ~/test-exit-status" > askap
chmod +x askap
