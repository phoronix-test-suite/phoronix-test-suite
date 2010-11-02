#!/bin/sh

unzip -o UrbanTerror_41_FULL.zip

mv UrbanTerror UrbanTerror_
cd UrbanTerror_/
chmod +x ioUrbanTerror.i386
chmod +x ioUrbanTerror.x86_64
chmod +x ioUrbanTerror.app/Contents/MacOS/ioUrbanTerror.ub
cd ..

unzip -o urbanterror-q3ut4-4.zip
rm -f UrbanTerror_/q3ut4/autoexec.cfg
mv autoexec.cfg UrbanTerror_/q3ut4/
mv pts1.dm_68 UrbanTerror_/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror_/

if [ \$OS_TYPE = \"MacOSX\" ]
then
	mkdir -p ~/Library/Application\ Support/Quake3
	./ioUrbanTerror.app/Contents/MacOS/ioUrbanTerror.ub \$@ > \$LOG_FILE 2>&1
else
	case \$OS_ARCH in
		\"x86_64\" )
			./ioUrbanTerror.x86_64 \$@ > \$LOG_FILE 2>&1
			;;
		* )
			./ioUrbanTerror.i386 \$@ > \$LOG_FILE 2>&1
			;;
	esac
fi" > urbanterror
chmod +x urbanterror
