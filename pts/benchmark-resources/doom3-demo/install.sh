#!/bin/sh

cd $1

if [ ! -f doom3-demo-linux.run ]
  then
     wget ftp://ftp.idsoftware.com/idstuff/doom3/linux/doom3-linux-1.1.1286-demo.x86.run -O doom3-demo-linux.run
fi
if [ ! -f doom3-pts-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/doom3-pts-demo-1.tar.bz2 -O doom3-pts-demo.tar.bz2
fi

chmod +x doom3-demo-linux.run

./doom3-demo-linux.run --noexec --target .
ln bin/Linux/x86/doom.x86 doom3-real

echo "#!/bin/sh
./doom3-real \$@ | grep fps" > doom3
chmod +x doom3

tar -jxvf doom3-pts-demo.tar.bz2
mkdir demo/demos
mv -f doom3-pts-demo.demo demo/demos/doom3-pts-demo
