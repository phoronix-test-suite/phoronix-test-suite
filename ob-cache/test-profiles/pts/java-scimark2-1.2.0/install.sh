#!/bin/sh
echo "#!/bin/sh
java -jar scimark-2.2.jar -large > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > java-scimark2
chmod +x java-scimark2
