#!/bin/sh

tar -xf png-samples-1.tar.xz
unzip -o astcenc-4.0.0-macos-x64.zip
chmod +x bin/astcenc-avx2

echo "#!/bin/sh
./bin/astcenc-avx2 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
