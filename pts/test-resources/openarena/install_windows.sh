#!/bin/sh

unzip -o oa081.zip
unzip -o oa085p.zip

echo "#!/bin/sh
cd openarena-0.8.1/
openarena.exe \$@
mv stderr.txt \$LOG_FILE" > openarena
chmod +x openarena

cp openarena-benchmark-files-6.zip openarena-0.8.1/baseoa
cd openarena-0.8.1/baseoa
unzip -o openarena-benchmark-files-6.zip
