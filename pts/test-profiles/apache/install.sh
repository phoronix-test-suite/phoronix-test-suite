#!/bin/sh

mkdir $HOME/httpd_

tar -zxvf apache-ab-test-files-1.tar.gz
tar -zxvf httpd-2.2.17.tar.gz

cd httpd-2.2.17/
./configure --prefix=$HOME/httpd_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf httpd-2.2.17/
rm -rf httpd_/manual/

patch -p0 < CHANGE-APACHE-PORT.patch
mv -f test.html httpd_/htdocs/
mv -f pts.png httpd_/htdocs/

echo "#!/bin/sh
./httpd_/bin/ab \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > apache

chmod +x apache
