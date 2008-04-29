#!/bin/sh

cd $1

chmod +x ETQW-client-1.4-full.x86.run

unzip -o ETQW-client-1.4-full.x86.run

echo "#!/bin/sh
cd data
./etqw.x86 \$@ | grep fps" > etqw
chmod +x etqw

if [ ! -f etqw-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/etqw-demo-1.tar.bz2 -O etqw-demo.tar.bz2
fi
tar -jxvf etqw-demo.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm

echo "ET:QW Game Files (*.pk4) Must Be Copied Into $1/base"

