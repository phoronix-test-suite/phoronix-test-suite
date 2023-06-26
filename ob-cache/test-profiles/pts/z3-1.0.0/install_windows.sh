#!/bin/sh
unzip -o z3-4.12.1-x64-win.zip
chmod +x z3-4.12.1-x64-win/bin/z3.exe
echo "#!/bin/sh
./z3-4.12.1-x64-win/bin/z3.exe \$1 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/z3
chmod +x ~/z3
