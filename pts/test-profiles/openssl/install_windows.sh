#!/bin/sh

echo "#!/bin/sh
cd C:\OpenSSL-Win32\bin
openssl.exe speed rsa4096 > \$LOG_FILE 2>&1" > openssl
chmod +x openssl


Win32OpenSSL_Light-1_0_0a.exe
