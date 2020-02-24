#!/bin/sh

rm -rf  lc0-0.23.2
unzip -o lc0-0.23.2.zip
cd lc0-0.23.2
mkdir build
meson build --buildtype release -Dgtest=false
cd build
ninja
echo $? > ~/install-exit-status

cd ~
cp -f 9f44992aafe2f58e17d1d2565ba8e1ad6ae995f2d6be371a94b821221841f1d9 lc0-0.23.2/build/

echo "#!/bin/sh
cd  lc0-0.23.2/build/
./lc0 \$@ --threads=\$NUM_CPU_CORES -w 9f44992aafe2f58e17d1d2565ba8e1ad6ae995f2d6be371a94b821221841f1d9 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
