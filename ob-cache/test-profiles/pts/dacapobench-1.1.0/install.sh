#!/bin/sh
unzip -o dacapo-23.11-chopin.zip
echo "#!/bin/sh
java -jar dacapo-23.11-chopin.jar -n 3 --window 5 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > dacapobench
chmod +x dacapobench
