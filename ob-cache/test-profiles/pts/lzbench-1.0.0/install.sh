#!/bin/sh

gunzip -k linux-4.9.tar.gz
unzip -o lzbench-20170808.zip
rm -rf lzbench_
mv lzbench lzbench_

cd lzbench_
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd lzbench_
./lzbench -t16,16 -v \$@ ../linux-4.9.tar > \$LOG_FILE
echo \$? > ~/test-exit-status" > lzbench
chmod +x lzbench
