#!/bin/sh

unzip -o unvanquished_0.53.0.zip
cd unvanquished_0.53.0
unzip -o windows-amd64.zip
cd ~

xz -d -k unvanquished-benchmark_0.53.0.dm_86.xz
mkdir -p "$USERPROFILE\Documents\My Games\Unvanquished\demos"
mkdir -p "$USERPROFILE\Documents\My Games\Unvanquished\config"
mv unvanquished-benchmark_0.53.0.dm_86 "$USERPROFILE\Documents\My Games\Unvanquished\demos"

echo "#!/bin/sh
cd unvanquished_0.53.0
./daemon.exe \$@ > \$LOG_FILE
cat \"\$USERPROFILE\Documents\My Games\Unvanquished\daemon.log\" >> \$LOG_FILE" > unvanquished
chmod +x unvanquished
