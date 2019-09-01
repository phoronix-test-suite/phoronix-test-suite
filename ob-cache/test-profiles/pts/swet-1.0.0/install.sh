#!/bin/sh

tar -xf swet-1.5.16-src.tar.gz
cd swet1
./configure
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd swet1
./swet \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > swet
chmod +x swet
