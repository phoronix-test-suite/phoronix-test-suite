#!/bin/sh

tar -xf libplacebo-2.72.2.tar.gz

cd libplacebo-2.72.2/
mkdir build
cd build

meson -Dbench=true ..
ninja
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd libplacebo-2.72.2/build/
./src/bench > \$LOG_FILE
echo \$? > ~/test-exit-status" > libplacebo
chmod +x libplacebo
