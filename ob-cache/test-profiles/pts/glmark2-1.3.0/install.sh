#!/bin/sh

tar -xf glmark2-20210830.tar.xz
cd glmark2-master

python3 ./waf configure --with-flavors=x11-gl --prefix=$HOME
python3 ./waf build
python3 ./waf install
echo $? > ~/install-exit-status

cd ~
rm -rf glmark2-master

echo "#!/bin/sh
cd bin/
./glmark2 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glmark2
chmod +x glmark2
