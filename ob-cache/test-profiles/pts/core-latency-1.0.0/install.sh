#!/bin/sh

unzip -o core-latency-20190614.zip
cd core-latency-master

make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd core-latency-master
./core-latency > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > core-latency
chmod +x core-latency
