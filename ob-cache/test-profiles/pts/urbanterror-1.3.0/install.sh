#!/bin/sh

unzip -o UrbanTerror432_full.zip

cd UrbanTerror43/
chmod +x Quake3-UrT.app/Contents/MacOS/Quake3-UrT.i386
cd ~

unzip -o urbanterror-43-1.zip
rm -f UrbanTerror43/q3ut4/autoexec.cfg
mv autoexec.cfg UrbanTerror43/q3ut4/
mkdir UrbanTerror43/q3ut4/demos/
mv pts-ut43.urtdemo UrbanTerror43/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror43/

if [ \$OS_TYPE = \"MacOSX\" ]
then
	mkdir -p ~/Library/Application\ Support/Quake3
	./Quake3-UrT.app/Contents/MacOS/Quake3-UrT.i386 \$@ > \$LOG_FILE 2>&1
else
	case \$OS_ARCH in
		\"x86_64\" )
			./Quake3-UrT.x86_64 \$@ > \$LOG_FILE 2>&1
			;;
		* )
			./Quake3-UrT.i386 \$@ > \$LOG_FILE 2>&1
			;;
	esac
fi" > urbanterror
chmod +x urbanterror
