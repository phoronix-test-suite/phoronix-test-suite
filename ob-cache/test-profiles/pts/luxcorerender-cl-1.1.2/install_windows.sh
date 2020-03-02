#!/bin/sh

unzip -o luxcorerender-v2.2-win64-opencl-sdk.zip
unzip -o DLSC.zip
unzip -o RainbowColorsAndPrism.zip
unzip -o LuxCore2.2Benchmark.zip
unzip -o Food.zip
cp -f luxcorerender-v2.2-win64-opencl-sdk/lib/* luxcorerender-v2.2-win64-opencl-sdk/bin

echo "#!/bin/sh
./luxcorerender-v2.2-win64-opencl-sdk/bin/luxcoreconsole.exe \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender-cl
chmod +x luxcorerender-cl
