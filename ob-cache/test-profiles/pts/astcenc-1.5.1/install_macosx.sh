#!/bin/sh
tar -xf png-samples-1.tar.xz
unzip -o astcenc-4.7.0-macos-universal.zip
chmod +x bin/astcenc
echo "#!/bin/sh
./bin/astcenc \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
