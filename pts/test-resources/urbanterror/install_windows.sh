#!/bin/sh

unzip -o UrbanTerror_41_FULL.zip

mv UrbanTerror UrbanTerror_

unzip -o urbanterror-q3ut4-4.zip
rm -f UrbanTerror_/q3ut4/autoexec.cfg
cp autoexec.cfg UrbanTerror_/q3ut4/
cp pts1.dm_68 UrbanTerror_/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror_/
ioUrbanTerror.exe \$@
mv q3ut4/qconsole.log \$LOG_FILE" > urbanterror
chmod +x urbanterror
