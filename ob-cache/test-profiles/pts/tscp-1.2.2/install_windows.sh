#!/bin/sh

unzip -o tscp181.exe.zip
echo "#!/bin/sh
echo bench | ./tscp181.exe \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tscp
chmod +x tscp
