#!/bin/sh
tar -xf png-samples-1.tar.xz
unzip -o astcenc-4.7.0-windows-x64.zip
chmod +x bin/astcenc-avx2.exe
echo "#!/bin/sh
./bin/astcenc-avx2.exe \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
