#!/bin/sh
# Windows support currently disabled since it doesn't auto quit at the end...
unzip -o xmrig-6.21.0-msvc-win64.zip
chmod +x xmrig-6.21.0/xmrig.exe
echo "#!/bin/sh
cd 6.21.0
./xmrig.exe --no-color --threads=\$NUM_CPU_CORES \$@ -l out.log
cat out.log > \$LOG_FILE" > xmrig
chmod +x xmrig
