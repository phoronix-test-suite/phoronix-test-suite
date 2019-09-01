#!/bin/sh

unzip -o UrbanTerror432_full.zip

unzip -o urbanterror-43-1.zip
cp autoexec.cfg UrbanTerror43/q3ut4/
mkdir UrbanTerror43/q3ut4/demos/
mv pts-ut43.urtdemo UrbanTerror43/q3ut4/demos/

echo "#!/bin/sh
cd UrbanTerror43/
./Quake3-UrT.exe \$@
mv q3ut4/qconsole.log \$LOG_FILE" > urbanterror
chmod +x urbanterror
