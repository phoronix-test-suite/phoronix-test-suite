#!/bin/sh

unzip -o DLSC-23.zip
unzip -o RainbowColorsAndPrism-23.zip
unzip -o LuxCore2.1Benchmark-23.zip
unzip -o DanishMood-23.zip
unzip -o OrangeJuice-23.zip

echo "#!/bin/bash
[ ! -d \"/Volumes/luxcorerender-v2.6-mac64/\" ] && hdid luxcorerender-v2.6-mac64.dmg

/Volumes/luxcorerender-v2.6-mac64/LuxCore.app/Contents/MacOS/luxcoreconsole \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
