#!/bin/sh

cd $1

unzip -o ETQW-demo2-client-full.r1.x86.run

echo "#!/bin/sh
cd data/
./etqw.x86 \$@ | grep fps" > etqw
chmod +x etqw

tar -jxvf etqw-demo-demo-1.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm

# etqw_pts.cfg
echo "
set com_unlockFPS \"1\"
timenetdemo pts
wait 2752
echo ======================
echo wait '# of frames + 2'
echo timenetdemo ended
echo quit
echo ======================
quit" > data/base/etqw_pts.cfg
