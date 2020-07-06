#!/bin/sh

unzip -o mixbench-20200623.zip
cd mixbench-master

make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd mixbench-master
./\$1 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mixbench
chmod +x mixbench
