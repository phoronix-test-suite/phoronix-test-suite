#!/bin/sh

chmod +x et-linux-2.60.x86.run
./et-linux-2.60.x86.run --target et-original --noexec

tar -xzvf etlegacy-v2.77-x86_64.tar.gz

cp et-original/etmain/*.pk3 etlegacy-v2.77-x86_64/etmain
rm -rf et-original

mkdir etlegacy-v2.77-x86_64/etmain/demos/
unzip -o etlegacy277-pts1.zip
mv -f etlegac277-pts1.dm_84 etlegacy-v2.77-x86_64/etmain/demos/

echo "#!/bin/sh
cd etlegacy-v2.77-x86_64/
LD_LIBRARY_PATH=.:\$LD_LIBRARY_PATH ./etl \$@ > \$LOG_FILE 2>&1" > etlegacy
chmod +x etlegacy
