#!/bin/sh

unzip -o FAHBench-2.3.1-win64.zip
echo "#!/bin/sh
cd FAHBench-2.3.1-win64
./FAHBench-cmd.exe \$@ > \$LOG_FILE" > fahbench
chmod +x fahbench
