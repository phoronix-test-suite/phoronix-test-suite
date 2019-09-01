#!/bin/sh

unzip -o y-cruncher-windows.zip

echo "#!/bin/sh
cd y-cruncher\ v0.7.7.9501/
rm -f Pi*
./y-cruncher.exe \$@
cat Pi\ -\ 2* > \$LOG_FILE" > y-cruncher
chmod +x y-cruncher
