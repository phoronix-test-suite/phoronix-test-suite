#!/bin/sh

chmod +x worldofpadman.run
chmod +x wop_patch_1_2.run

./worldofpadman.run --noexec --target wop11
./wop_patch_1_2.run --noexec --target wop12patch

mkdir wop-install/
mkdir wop-install/wop/

cd wop11/

tar -xf wop-data.tar
mv *.cfg ../wop-install/wop/
mv *.pk3 ../wop-install/wop/

cd ..

cd wop12patch/

mv bin/Linux/x86/WoP ../wop-install/WoP32
mv bin/Linux/x86_64/WoP ../wop-install/WoP64

tar -xf wop-engine.i386.tar
tar -xf wop-engine.x86_64.tar

mv wop-engine.i386 ../wop-install
mv wop-engine.x86_64 ../wop-install

tar -xf wop-data-1.2.tar
mv *.pk3 ../wop-install/wop/

cd ..

rm -rf wop11/
rm -rf wop12patch/

tar -zxvf wop-benchmark-1.tar.gz
mkdir ~/.WoPadman/
mkdir ~/.WoPadman/wop/
mv wop_config.cfg ~/.WoPadman/wop/
mv demos/ ~/.WoPadman/wop/

echo "#!/bin/sh
cd wop-install/

case \$OS_ARCH in
	\"x86_64\" )
	./WoP64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	./WoP32 \$@ > \$LOG_FILE 2>&1
	;;
esac" > padman
chmod +x padman
