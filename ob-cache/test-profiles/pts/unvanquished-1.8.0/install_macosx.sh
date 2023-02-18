#!/bin/sh
unzip -o unvanquished_0.54.0.zip
cd unvanquished_0.54.0
unzip -o macos-amd64.zip
cd ~
xz -d -k unvanquished-benchmark_0.54.0.dm_86.xz
mkdir -p ~/Library/Application\ Support/Unvanquished/demos
mkdir -p ~/Library/Application\ Support/Unvanquished/config
mv unvanquished-benchmark_0.54.0.dm_86 ~/Library/Application\ Support/Unvanquished/demos
echo "#!/bin/sh
cd unvanquished_0.54.0
./Unvanquished.app/Contents/MacOS/unvanquished.sh \$@ > \$LOG_FILE 2>&1" > unvanquished
chmod +x unvanquished
