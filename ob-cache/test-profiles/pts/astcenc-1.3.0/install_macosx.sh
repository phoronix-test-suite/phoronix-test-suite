#!/bin/sh

tar -xf png-samples-1.tar.xz
unzip -o astcenc-3.2-macosx64.zip
mv astcenc astcenc-3.2-macos-x64
chmod +x astcenc-3.2-macos-x64/astcenc-avx2

echo "#!/bin/sh
./astcenc-3.2-macos-x64/astcenc-avx2 -tl sample-4.png 1.png 8x6 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
