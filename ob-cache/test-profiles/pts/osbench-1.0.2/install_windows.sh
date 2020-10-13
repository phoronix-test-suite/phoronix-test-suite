#!/bin/sh

unzip -o osbench-win64-20170529.zip
mv osbench osbench-windows
mkdir osbench-windows/target

echo "#!/bin/sh
cd osbench-windows
./\$@ > \$LOG_FILE" > osbench
chmod +x osbench
