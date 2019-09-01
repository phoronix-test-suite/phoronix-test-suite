#!/bin/sh

tar -zxvf glmark2-20170617.tar.gz
cd glmark2-20170617

./waf configure --with-flavors=x11-gl --prefix=$HOME
./waf build
./waf install
echo $? > ~/install-exit-status

cd ~
rm -rf glmark2-20170617

echo "#!/bin/sh
cd bin/
./glmark2 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glmark2
chmod +x glmark2
