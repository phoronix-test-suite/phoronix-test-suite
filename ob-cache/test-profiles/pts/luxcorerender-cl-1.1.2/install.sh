#!/bin/sh

tar -xf luxcorerender-v2.2-linux64-opencl-sdk.tar.bz2
unzip -o DLSC.zip
unzip -o RainbowColorsAndPrism.zip
unzip -o LuxCore2.1Benchmark.zip
unzip -o Food.zip

echo "#!/bin/sh
LD_LIBRARY_PATH=\$HOME/LuxCore-opencl-sdk/lib:\$LD_LIBRARY_PATH ./LuxCore-opencl-sdk/bin/luxcoreconsole \$@  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luxcorerender-cl
chmod +x luxcorerender-cl
