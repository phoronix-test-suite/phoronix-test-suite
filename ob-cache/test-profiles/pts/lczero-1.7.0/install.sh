#!/bin/sh
rm -rf lc0-0.30.0/
tar -xf lc0-0.30.0.tar.gz
cd lc0-0.30.0/
mkdir build
meson build --buildtype release -Dgtest=false
cd build
ninja
echo $? > ~/install-exit-status
cd ~
gunzip t1-256x10-distilled-swa-2432500.pb.gz
cp -f t1-256x10-distilled-swa-2432500.pb lc0-0.30.0/build/
echo "#!/bin/bash
cd  lc0-0.30.0/build/
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
./lc0 \$@ --threads=\$THREADCOUNT -w t1-256x10-distilled-swa-2432500.pb > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
