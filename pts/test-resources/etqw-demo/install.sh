#!/bin/sh

unzip -o ETQW-demo2-client-full.r1.x86.run

echo "#!/bin/sh
cd data/
./etqw.x86 \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > etqw
chmod +x etqw

tar -jxvf etqw-demo-files-3.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm
mv -f etqw-pts.cfg data/base/etqw-pts.cfg
