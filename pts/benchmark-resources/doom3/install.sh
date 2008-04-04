#!/bin/sh

cp -f doom3-pts.cfg $1
cd $1

if [ ! -f doom3-linux.run ]
  then
     wget ftp://ftp.idsoftware.com/idstuff/doom3/linux/doom3-linux-1.3.1.1304.x86.run -O doom3-linux.run
fi

chmod +x doom3-linux.run

./doom3-linux.run --noexec --target .
ln bin/Linux/x86/doom.x86 doom3-real
mv -f doom3-pts.cfg base/doom3-pts.cfg

echo "#!/bin/sh
./doom3-real \$@ | grep fps" > doom3
chmod +x doom3

echo "Doom 3 Game Files (*.pk4) Must Be Copied Into $1/base"
echo "Also Copy Your Game Key File To ~/.doom3/base/doomkey (If Not Already There)"

