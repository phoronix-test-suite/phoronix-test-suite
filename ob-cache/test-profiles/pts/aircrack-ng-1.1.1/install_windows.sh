#!/bin/sh
unzip -o aircrack-ng-1.3-win.zip

echo "#!/bin/sh
cd aircrack-ng-1.3-win
./bin/64bit/aircrack-ng.exe \$@  2>&1 | tr '\\r' '\\n' | awk -v max=0 '{if(\$1>max){max=\$1}}END{print max \" k/s\"}' > \$LOG_FILE
echo \$? > ~/test-exit-status" > aircrack-ng
chmod +x aircrack-ng
