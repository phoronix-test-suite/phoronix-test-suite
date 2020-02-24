#!/bin/sh

unzip -o primesieve-7.2-win64-console.zip

echo "#!/bin/sh
./primesieve.exe \$@ > \$LOG_FILE" > primesieve-test
chmod +x primesieve-test
