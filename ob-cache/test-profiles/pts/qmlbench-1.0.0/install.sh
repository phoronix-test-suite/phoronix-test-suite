#!/bin/sh

unzip -o qmlbench-20180715.zip
cd qmlbench-master/
qmake
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd qmlbench-master/
./src/qmlbench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > qmlbench
chmod +x qmlbench
