#!/bin/sh

cd $1

if [ ! -f etqw-linux.run ]
  then
     wget ftp://ftp.idsoftware.com/idstuff/etqw/ETQW-client-1.4-full.x86.run -O etqw-linux.run
fi

chmod +x etqw-linux.run

unzip -o etqw-linux.run
ln data/etqw.x86 etqw-real

echo "#!/bin/sh
./etqw-real \$@ | grep fps" > etqw
chmod +x etqw

if [ ! -f etqw-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/etqw-demo-1.tar.bz2 -O etqw-demo.tar.bz2
fi
tar -jxvf etqw-demo.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm

echo "ET:QW Game Files (*.pk4) Must Be Copied Into $1/base"

