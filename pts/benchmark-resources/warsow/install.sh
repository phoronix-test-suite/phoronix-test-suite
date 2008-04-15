#!/bin/sh

cd $1

if [ ! -f warsow_0.42_unified.zip ]
  then
     wget http://www.speltips.org/~web1_mans/files/warsow_0.42_unified.zip -O warsow_0.42_unified.zip
fi

if [ ! -f pts-warsow-04.wd10 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-warsow-04.wd10 -O pts-warsow-04.wd10
fi

unzip -o warsow_0.42_unified.zip
cp pts-warsow-04.wd10 warsow_0.42_unified/basewsw/demos
cd warsow_0.42_unified/
chmod +x warsow.x86_64
chmod +x warsow.i386

echo "#!/bin/sh
cd warsow_0.42_unified/
case \`uname -m\` in
	\"x86_64\" )
	./warsow.x86_64 \$@ | grep fps
	;;
	* )
	./warsow.i386 \$@ | grep fps
	;;
esac" > warsow
chmod +x warsow
