#!/bin/sh

unzip -o luxmark-windows64-v3.1.zip

echo "#!/bin/sh
cd LuxMark-v3.1
./luxmark.exe \$@ > \$LOG_FILE" > luxmark
chmod +x luxmark
