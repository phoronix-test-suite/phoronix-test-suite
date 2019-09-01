#!/bin/sh

rm -rf povray-3.7.0.7/
tar -xf povray-3.7.0.7.tar.xz
cd povray-3.7.0.7/

cd unix/
autoupdate
./prebuild.sh
cd ..
automake --add-missing
LIBS="-lboost_system" ./configure COMPILED_BY="PhoronixTestSuite" --with-boost-thread=boost_thread
make -j 4
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd povray-3.7.0.7/
echo 1 | ./unix/povray -benchmark > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > povray
chmod +x povray
