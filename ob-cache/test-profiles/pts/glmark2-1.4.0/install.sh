#!/bin/sh
tar -xf glmark2-2023.01.tar.gz
cd glmark2-2023.01
python3 ./waf configure --with-flavors=x11-gl --prefix=$HOME
python3 ./waf build
python3 ./waf install
echo $? > ~/install-exit-status
cd ~
rm -rf glmark2-2023.01
echo "#!/bin/sh
./bin/glmark2 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glmark2
chmod +x glmark2
