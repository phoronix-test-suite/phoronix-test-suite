#!/bin/sh

tar -xf synthmark-20201109.tar.xz
cd synthmark-master
sed -i 's/-Werror/-Wno-error/' linux/Makefile
make -f linux/Makefile
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd synthmark-master
./synthmark.app \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > synthmark
chmod +x synthmark
