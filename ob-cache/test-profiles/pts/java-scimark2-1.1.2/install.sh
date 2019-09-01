#!/bin/sh

unzip -o scimark2lib.zip

echo "#!/bin/sh
java jnt.scimark2.commandline > \$LOG_FILE
echo \$? > ~/test-exit-status" > java-scimark2
chmod +x java-scimark2
