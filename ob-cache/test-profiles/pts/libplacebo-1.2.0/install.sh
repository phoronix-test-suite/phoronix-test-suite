#!/bin/sh
tar -xf libplacebo-6.338.2.tar.gz
cd libplacebo-6.338.2
mkdir build
cd build
pip3 install --user glad2
meson -Dbench=true -Dopengl=disabled -Ddemos=false ..
ninja
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd libplacebo-6.338.2/build/
./src/bench > \$LOG_FILE
echo \$? > ~/test-exit-status" > libplacebo
chmod +x libplacebo
