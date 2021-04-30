#!/bin/sh

tar -xf qmlbench-20210302.tar.xz
cd qmlbench-20210302
qmake
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd qmlbench-20210302
./src/qmlbench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > qmlbench
chmod +x qmlbench
