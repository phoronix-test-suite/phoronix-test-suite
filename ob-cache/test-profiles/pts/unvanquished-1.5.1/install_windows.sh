#!/bin/sh

unzip -o unvanquished-0.26.0-universal.zip
mv unvanquished unvanquished-game

unzip -o unvanquished-26-1.zip
mkdir ~/.unvanquished
mkdir ~/.unvanquished/demos
mv pts26. ~/.unvanquished/demos

cd ~

echo "#!/bin/sh
cd unvanquished-game/
daemon.exe \$@ > \$LOG_FILE" > unvanquished

