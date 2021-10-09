#!/bin/sh

echo "#!/bin/sh
java -jar renaissance-mit-0.12.0.jar \$@ --csv \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > renaissance
chmod +x renaissance
