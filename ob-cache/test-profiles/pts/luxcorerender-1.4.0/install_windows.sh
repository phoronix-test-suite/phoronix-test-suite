#!/bin/sh

unzip -o luxcorerender-v2.6-win64-sdk.zip
unzip -o DLSC-23.zip
unzip -o RainbowColorsAndPrism-23.zip
unzip -o LuxCore2.1Benchmark-23.zip
unzip -o DanishMood-23.zip
unzip -o OrangeJuice-23.zip

cp -f luxcorerender-v2.6-win64-sdk/lib/* luxcorerender-v2.6-win64-sdk/bin

echo "#!/bin/sh
./luxcorerender-v2.6-win64-sdk/bin/luxcoreconsole.exe \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
