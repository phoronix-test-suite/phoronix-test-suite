#!/bin/sh

tar -xf png-samples-1.tar.xz
unzip -o astcenc-2.0-macos-x64.zip
chmod +x astcenc-2.0-macos-x64/astcenc-avx2

echo "#!/bin/sh
./astcenc-2.0-macos-x64/astcenc-avx2 -tl sample-4.png 1.png 8x6 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > basis
chmod +x basis
