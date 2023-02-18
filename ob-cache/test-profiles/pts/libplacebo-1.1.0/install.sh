#!/bin/sh
tar -xf libplacebo-5.229.1.tar.gz
cd libplacebo-5.229.1
mkdir build
cd build
pip3 install --user glad2
meson -Dbench=true ..
ninja
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd libplacebo-5.229.1/build/
./src/bench > \$LOG_FILE
echo \$? > ~/test-exit-status" > libplacebo
chmod +x libplacebo
