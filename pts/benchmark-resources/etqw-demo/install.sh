#!/bin/sh

cd $1

if [ ! -f ETQW-demo2-client-full.r1.x86.run ]
  then
     wget ftp://ftp.idsoftware.com/idstuff/etqw/ETQW-demo2-client-full.r1.x86.run -O ETQW-demo2-client-full.r1.x86.run
fi
if [ ! -f etqw-demo-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/etqw-demo-demo-1.tar.bz2 -O etqw-demo-demo.tar.bz2
fi

unzip -o ETQW-demo2-client-full.r1.x86.run

echo "#!/bin/sh\ncd data/\n./etqw.x86 \$@ | grep fps" > etqw
chmod +x etqw

tar -jxvf etqw-demo-demo.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm
