#!/bin/sh

unzip -o UrbanTerror_41_FULL.zip

mv UrbanTerror UrbanTerror_

unzip -o urbanterror-q3ut4-3.zip
rm -f UrbanTerror_/q3ut4/autoexec.cfg
mv autoexec.cfg UrbanTerror_/q3ut4/
mv pts1.dm_68 UrbanTerror_/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror_/
ioUrbanTerror.exe \$@ > \$LOG_FILE 2>&1" > urbanterror
chmod +x urbanterror
