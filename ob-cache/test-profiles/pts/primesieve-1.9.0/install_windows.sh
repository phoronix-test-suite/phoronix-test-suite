#!/bin/sh

unzip -o primesieve-8.0-win-x64.zip
chmod +x primesieve.exe

echo "#!/bin/sh
./primesieve.exe \$@ > \$LOG_FILE" > primesieve-test
chmod +x primesieve-test
