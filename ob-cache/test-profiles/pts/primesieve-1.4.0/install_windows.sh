#!/bin/sh

unzip -o primesieve-6.2-win64-console.zip

echo "#!/bin/sh
primesieve.exe \$@ > \$LOG_FILE" > primesieve
chmod +x primesieve
