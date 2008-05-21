#!/bin/sh

cd $1

unzip -o warsow_0.42_unified.zip
cp pts-warsow-04.wd10 warsow_0.42_unified/basewsw/demos
cd warsow_0.42_unified/
chmod +x warsow.x86_64
chmod +x warsow.i386
cd ..

echo "#!/bin/sh
cd warsow_0.42_unified/
case \`uname -m\` in
	\"x86_64\" )
	./warsow.x86_64 \$@ | grep seconds
	;;
	* )
	./warsow.i386 \$@ | grep seconds
	;;
esac" > warsow
chmod +x warsow
