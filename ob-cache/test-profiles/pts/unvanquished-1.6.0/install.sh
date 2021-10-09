#!/bin/sh

unzip -o unvanquished_0.52.1.zip
cd unvanquished_0.52.1
unzip -o linux-amd64.zip
cd ~

unzip -o unvanquished-0521-demo.zip
mkdir -p ~/.local/share/unvanquished/demos
mkdir -p ~/.local/share/unvanquished/config
mv pts7.dm_86 ~/.local/share/unvanquished/demos

echo "#!/bin/sh
cd unvanquished_0.52.1
./daemon \$@ > \$LOG_FILE 2>&1 &

# Daemon seems to have dropped timedemoquit-like functionality as used in unvanquished-1.5.1 test profile and prior
sleep 1
tail -f \$LOG_FILE | sed '/Demo completed/ q'
sleep 1
killall -9 daemon

" > unvanquished
chmod +x unvanquished
