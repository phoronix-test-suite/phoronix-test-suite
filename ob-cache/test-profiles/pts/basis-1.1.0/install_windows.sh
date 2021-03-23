#!/bin/sh

tar -xf png-samples-1.tar.xz
unzip -o basisu_v113_windows_linux_bins.zip
chmod +x basisu.exe

echo "#!/bin/sh
./basisu.exe \$@ sample-*.png > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > basis
chmod +x basis
