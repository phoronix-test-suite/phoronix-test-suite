#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/httpd_

tar -xvf apache-ab-test-files-1.tar.gz
tar -xvf httpd-2.2.11.tar.gz

cd httpd-2.2.11/
./configure --prefix=$THIS_DIR/httpd_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf httpd-2.2.11/

patch -p0 < CHANGE-APACHE-PORT.patch
mv -f test.html httpd_/htdocs/
mv -f pts.png httpd_/htdocs/

echo "#!/bin/sh
THIS_DIR=\$(pwd)
./httpd_/bin/apachectl -k start -f \$THIS_DIR/httpd_/conf/httpd.conf
sleep 5
./httpd_/bin/ab \$@ > \$LOG_FILE 2>&1
./httpd_/bin/apachectl -k stop" > apache

chmod +x apache
