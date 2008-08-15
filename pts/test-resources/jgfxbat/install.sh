#!/bin/sh

unzip -o GfxBAT.zip
chmod +x runbat.sh

echo "#!/bin/sh

export JAVA_HOME=/usr
sh ./runbat.sh > \$THIS_RUN_TIME.result
if [ \$? -eq 0 ]; then
   echo 'PASS'
else
   echo 'FAIL'
fi
" > jgfxbat
chmod +x jgfxbat
