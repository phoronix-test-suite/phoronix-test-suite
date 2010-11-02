#!/bin/sh

unzip -o bork-1.4.zip

echo "#!/bin/sh
cd bork-1.4/
BORK_PASSWORD=phoronixtestsuite123 ./bork.sh ../encryptfile > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > bork
chmod +x bork
