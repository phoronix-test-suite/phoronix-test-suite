#!/bin/sh

cd $1

if [ ! -f quake4-linux.run ]
  then
     wget ftp://ftp.idsoftware.com/idstuff/quake4/linux/quake4-linux-1.4.2.x86.run -O quake4-linux.run
fi
if [ ! -f quake4-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/quake4-demo-1.tar.bz2 -O quake4-demo.tar.bz2
fi

chmod +x quake4-linux.run

./quake4-linux.run --noexec --target .
ln bin/Linux/x86/quake4.x86 quake4-real
chmod +x quake4-real

echo "#!/bin/sh\n./quake4-real \$@ | grep fps" > quake4
chmod +x quake4

tar -jxvf quake4-demo.tar.bz2
mkdir q4base/demos
mv -f pts.demo q4base/demos/pts.demo

echo "Quake 4 Game Files (*.pk4) Must Be Copied Into $1/q4base"
echo "Also Copy Your Game Key File To ~/.quake4/q4base/quake4key (If Not Already There)"
