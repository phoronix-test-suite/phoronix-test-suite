#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf betsy-1.1-beta.tar.gz
cd betsy-1.1-beta

mkdir build
cd build

cmake -DCMAKE_BUILD_TYPE=Release -GNinja ..
ninja
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd betsy-1.1-beta/bin/Release/
./betsy ~/sample-4.png \$@ out.ktx > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > betsy
chmod +x betsy
