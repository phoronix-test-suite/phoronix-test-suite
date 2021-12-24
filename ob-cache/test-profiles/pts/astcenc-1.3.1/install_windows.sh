#!/bin/sh

tar -xf png-samples-1.tar.xz
unzip -o astcenc-3.2-windowsx64.zip
mv astcenc astcenc-windows-x64
chmod +x astcenc-windows-x64/astcenc-avx2.exe

echo "#!/bin/sh
./astcenc-windows-x64/astcenc-avx2.exe -tl sample-4.png 1.png 8x6 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
