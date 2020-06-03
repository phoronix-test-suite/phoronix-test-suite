#!/bin/sh

unzip -o lightsmark-2008-windows.zip

echo "#!/bin/sh
cd bin/x64/
./backend.exe \$@
mv ~/log.txt \$LOG_FILE" > lightsmark
chmod +x lightsmark
