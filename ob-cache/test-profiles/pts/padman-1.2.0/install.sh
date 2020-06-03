#!/bin/sh

unzip -o wop-1.5-unified.zip
unzip -o wop-1.5.x-to-1.6-patch-unified.zip

tar -xzvf wop-benchmark-1.tar.gz
mv wop_config.cfg wop
mv demos wop
chmod +x wop.x86_64
chmod +x wop.i386

echo "#!/bin/sh

case \$OS_ARCH in
	\"x86_64\" )
	./wop.x86_64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	./wop.i386 \$@ > \$LOG_FILE 2>&1
	;;
esac" > padman
chmod +x padman
