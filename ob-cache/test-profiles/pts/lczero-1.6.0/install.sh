#!/bin/sh

rm -rf lc0-0.28.0/
tar -xf lc0-0.28.0.tar.gz
cd lc0-0.28.0/

mkdir build
meson build --buildtype release -Dgtest=false
cd build
ninja
echo $? > ~/install-exit-status

cd ~
cp -f b30e742bcfd905815e0e7dbd4e1bafb41ade748f85d006b8e28758f1a3107ae3 lc0-0.28.0/build/

echo "#!/bin/bash
cd  lc0-0.28.0/build/
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./lc0 \$@ --threads=\$THREADCOUNT -w b30e742bcfd905815e0e7dbd4e1bafb41ade748f85d006b8e28758f1a3107ae3 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
