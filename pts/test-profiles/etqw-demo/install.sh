#!/bin/sh

unzip -o ETQW-demo2-client-full.r1.x86.run

echo "#!/bin/sh
cd data/
./etqw.x86 \$@ > \$LOG_FILE 2>&1" > etqw
chmod +x etqw

tar -jxvf etqw-demo-files-4.tar.bz2
mkdir data/base/demos
mv -f *.ndm data/base/demos/
mv -f etqw-pts*.cfg data/base/
