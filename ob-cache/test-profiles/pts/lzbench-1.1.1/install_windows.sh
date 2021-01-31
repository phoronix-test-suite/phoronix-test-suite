#!/bin/sh

gunzip -k linux-5.5.tar.gz
unzip -o lzbench-1.8-windows.zip

echo "#!/bin/sh
./lzbench18.exe -t10,10 -v \$@ linux-5.5.tar > \$LOG_FILE" > lzbench
chmod +x lzbench
