#!/bin/sh

rm -rf  lc0-0.25.0/
tar -xf lc0-0.25.0.tar.gz
cd lc0-0.25.0/
mkdir build
meson build --buildtype release -Dgtest=false
cd build
ninja
echo $? > ~/install-exit-status

cd ~
cp -f a7bbb6104419028cc720c8e2433c25f0b0f84a21b69a881b7dc7ffb35d7ddb69 lc0-0.25.0/build/

echo "#!/bin/sh
cd  lc0-0.25.0/build/
./lc0 \$@ --threads=\$NUM_CPU_CORES -w a7bbb6104419028cc720c8e2433c25f0b0f84a21b69a881b7dc7ffb35d7ddb69 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
