#!/bin/sh
unzip -o ethr_linux_100.zip
mv ethr ethr.bin
chmod +x ethr.bin
echo "#!/bin/sh
# Start server in case doing localhost test
./ethr.bin -s > /dev/null 2>&1 &
ETHR_SERVER_PID=\$!
sleep 3
./ethr.bin \$@ > \$LOG_FILE 2>1
kill \$ETHR_SERVER_PID
sleep 3" > ethr
chmod +x ethr
