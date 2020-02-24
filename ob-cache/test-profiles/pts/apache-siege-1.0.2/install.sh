#!/bin/sh

mkdir $HOME/httpd_

tar -zxvf apache-ab-test-files-1.tar.gz
tar -jxvf httpd-2.4.29.tar.bz2
tar -jxvf apr-util-1.6.1.tar.bz2
tar -jxvf apr-1.6.3.tar.bz2
mv apr-1.6.3 httpd-2.4.29/srclib/apr
mv apr-util-1.6.1 httpd-2.4.29/srclib/apr-util

cd httpd-2.4.29/
./configure --prefix=$HOME/httpd_ --with-included-apr
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf httpd-2.4.29/
rm -rf httpd_/manual/

patch -p0 < CHANGE-APACHE-PORT.patch
mv -f test.html httpd_/htdocs/
mv -f pts.png httpd_/htdocs/


cd ~
tar -xf siege-3.1.4.tar.gz
cd siege-3.1.4
./configure
make -j $NUM_CPU_CORES
cd utils
bash siege.config
cd ~

echo "#!/bin/sh
cd siege-3.1.4/src
./siege \$@ 2>&1 | grep -v HTTP > \$LOG_FILE
echo \$? > ~/test-exit-status" > apache-siege

chmod +x apache-siege
