#!/bin/sh

tar -xf luxcorerender-v2.6-linux64-sdk.tar.bz2
unzip -o DLSC-23.zip
unzip -o RainbowColorsAndPrism-23.zip
unzip -o LuxCore2.1Benchmark-23.zip
unzip -o DanishMood-23.zip
unzip -o OrangeJuice-23.zip

echo "#!/bin/sh
LD_LIBRARY_PATH=\$HOME/LuxCore-sdk/lib:\$LD_LIBRARY_PATH ./LuxCore-sdk/bin/luxcoreconsole \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender
chmod +x luxcorerender
