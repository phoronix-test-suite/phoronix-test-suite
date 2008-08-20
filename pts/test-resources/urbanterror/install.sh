#!/bin/sh

unzip -o UrbanTerror_41_FULL.zip

cd UrbanTerror/
chmod +x ioUrbanTerror.i386
chmod +x ioUrbanTerror.x86_64
cd ..

tar -xvf urbanterror-q3ut4-2.tar.gz
mv -f autoexec.cfg UrbanTerror/q3ut4/
mv -f pts1.dm_68 UrbanTerror/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror/

case \$OS_ARCH in
	\"x86_64\" )
	./ioUrbanTerror.x86_64 \$@ 2>&1 | grep fps
	;;
	* )
	./ioUrbanTerror.i386 \$@ 2>&1 | grep fps
	;;
esac" > urbanterror
chmod +x urbanterror
