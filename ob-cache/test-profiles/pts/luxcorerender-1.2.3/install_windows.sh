#!/bin/sh

unzip -o luxcorerender-v2.3-win64-sdk.zip
unzip -o DLSC.zip
unzip -o RainbowColorsAndPrism.zip
cp -f luxcorerender-v2.3-win64-sdk/lib/* luxcorerender-v2.3-win64-sdk/bin

echo "#!/bin/sh
./luxcorerender-v2.3-win64-sdk/bin/luxcoreconsole.exe \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
