#!/bin/bash

unzip -o Apache24-2.4.29-x64-vc14-r2-ah.zip

tar -xf apache-ab-test-files-1.tar.gz
mv -f test.html Apache24/htdocs/
mv -f pts.png Apache24/htdocs/

cd Apache24/conf
tail -n +2 httpd.conf > httpd.conf.2
echo "Define SRVROOT \"$DEBUG_HOME\Apache24\"
" > httpd.conf
cat httpd.conf.2 >> httpd.conf
cd ~

echo "#!/bin/sh
cd Apache24/bin
./ab.exe \$@ > \$LOG_FILE" > apache
chmod +x apache
