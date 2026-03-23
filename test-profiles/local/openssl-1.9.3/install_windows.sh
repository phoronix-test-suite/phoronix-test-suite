#!/bin/sh

unzip -o openssl-1.0.1g-x64_86-win64.zip
mv openssl.exe openssl-win64.exe

echo "#!/bin/sh
./openssl-win64.exe speed rsa4096 > \$LOG_FILE
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


