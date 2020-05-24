#!/bin/sh

unzip -o DLSC.zip
unzip -o RainbowColorsAndPrism.zip

echo "#!/bin/bash
[ ! -d \"/Volumes/luxcorerender-v2.3-mac64/\" ] && hdid luxcorerender-v2.3-mac64.dmg

/Volumes/luxcorerender-v2.3-mac64/LuxCore.app/Contents/MacOS/luxcoreconsole \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
