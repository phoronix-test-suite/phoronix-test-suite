#!/bin/sh

unzip -o unvanquished_0.53.0.zip
cd unvanquished_0.53.0
unzip -o linux-amd64.zip
chmod +x daemon
cd ~

xz -d -k unvanquished-benchmark_0.53.0.dm_86.xz
mkdir -p ~/.local/share/unvanquished/demos
mkdir -p ~/.local/share/unvanquished/config
mv unvanquished-benchmark_0.53.0.dm_86 ~/.local/share/unvanquished/demos

echo "#!/bin/sh
cd unvanquished_0.53.0
./daemon \$@ > \$LOG_FILE 2>&1" > unvanquished
chmod +x unvanquished
