#!/bin/sh

echo "#!/bin/sh
java -jar renaissance-mit-0.10.0.jar \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > renaissance
chmod +x renaissance
