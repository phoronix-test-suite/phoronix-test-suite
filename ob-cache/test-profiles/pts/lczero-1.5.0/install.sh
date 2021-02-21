#!/bin/sh

rm -rf  lc0-0.26.2/
tar -xf lc0-0.26.2.tar.gz
cd lc0-0.26.2/
mkdir build
meson build --buildtype release -Dgtest=false
cd build
ninja
echo $? > ~/install-exit-status

cd ~
cp -f b30e742bcfd905815e0e7dbd4e1bafb41ade748f85d006b8e28758f1a3107ae3 lc0-0.26.2/build/

echo "#!/bin/sh
cd  lc0-0.26.2/build/
./lc0 \$@ --threads=\$NUM_CPU_CORES -w b30e742bcfd905815e0e7dbd4e1bafb41ade748f85d006b8e28758f1a3107ae3 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
