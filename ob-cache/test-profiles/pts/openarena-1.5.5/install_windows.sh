#!/bin/sh

unzip -o openarena-0.8.8.zip
unzip -o openarena-088-1.zip
mv pts-openarena-088.cfg openarena-0.8.8/baseoa

echo "#!/bin/sh
cd openarena-0.8.8/
./openarena.exe \$@
mv stderr.txt \$LOG_FILE" > openarena
chmod +x openarena
