#!/bin/sh

unzip -o pts-warsow-15-1.zip

tar -xvf warsow_1.51_unified.tar.gz
chmod +x warsow_15/warsow.*
mkdir -p warsow_15/basewsw/demos
cp -f pts1.wdz20 warsow_15/basewsw/demos

echo "#!/bin/sh
cd warsow_15/
if [ \$OS_ARCH = \"x86_64\" ]
then
	./warsow.x86_64 \$@
else
	./warsow.i386 \$@
fi
cat ~/.warsow-1.5/basewsw/pts-log.log > \$LOG_FILE" > warsow
chmod +x warsow
