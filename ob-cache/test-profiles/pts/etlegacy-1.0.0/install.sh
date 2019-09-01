#!/bin/sh

chmod +x et-linux-2.60.x86.run
./et-linux-2.60.x86.run --target et-original --noexec

tar -xzvf etlegacy-v2.75-x86_64.tar.gz

cp et-original/etmain/*.pk3 etlegacy-v2.75-x86_64/etmain
rm -rf et-original

tar -zxvf et-demos-1.tar.gz
mv -f pts-etconfig.cfg etlegacy-v2.75-x86_64/etmain/
mkdir etlegacy-v2.75-x86_64/etmain/demos/
mv -f railgun.dm_83 etlegacy-v2.75-x86_64/etmain/demos/

echo "#!/bin/sh
cd etlegacy-v2.75-x86_64/
./etl \$@ > \$LOG_FILE 2>&1" > etlegacy
chmod +x etlegacy
