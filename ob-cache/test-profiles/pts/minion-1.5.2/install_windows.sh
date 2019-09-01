#!/bin/sh

rm -rf minion-1.8/
tar -zxf minion-1.8-windows.tar_.gz

echo "#!/bin/sh
cd minion-1.8/
./bin/minion.exe \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > minion
chmod +x minion
