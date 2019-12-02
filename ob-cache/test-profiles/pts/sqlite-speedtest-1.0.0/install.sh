#!/bin/sh

tar -xf sqlite-330-for-speedtest.tar.gz
cd sqlite
./configure
make speedtest1
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd sqlite
./speedtest1 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > sqlite-speedtest
chmod +x sqlite-speedtest
