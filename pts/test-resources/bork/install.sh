#!/bin/sh

unzip -o bork-1.4.zip

echo "#!/bin/sh
cd bork-1.4/
\$TIMER_START
BORK_PASSWORD=phoronixtestsuite123 ./bork.sh ../encryptfile > \$LOG_FILE
\$TIMER_STOP
rm -f ../encryptfile.bork" > bork
chmod +x bork
