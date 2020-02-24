#!/bin/sh

unzip -o iozone-windows-1.zip

echo "#!/bin/sh
cd iozone-windows
./iozone.exe \$@ > \$LOG_FILE" > ~/iozone
chmod +x ~/iozone
