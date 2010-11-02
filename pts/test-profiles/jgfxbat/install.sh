#!/bin/sh

unzip -o GfxBAT.zip
chmod +x runbat.sh

echo "#!/bin/sh

export JAVA_HOME=/usr
sh ./runbat.sh > \$THIS_RUN_TIME.result
if [ \$? -eq 0 ]; then
   echo 'PASS' > \$LOG_FILE
else
   echo 'FAIL' > \$LOG_FILE
fi" > jgfxbat
chmod +x jgfxbat
