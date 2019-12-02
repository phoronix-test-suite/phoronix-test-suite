#!/bin/sh

tar -xf luxcorerender-v2.2-linux64-sdk.tar.bz2
unzip -o DLSC.zip
unzip -o RainbowColorsAndPrism.zip

echo "#!/bin/sh
LD_LIBRARY_PATH=\$HOME/LuxCore-sdk/lib:\$LD_LIBRARY_PATH ./LuxCore-sdk/bin/luxcoreconsole \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
