#!/bin/sh

unzip -o lame3.100-64.zip
mv lame.exe lame_run.exe

echo "#!/bin/sh
./lame_run.exe -h \$TEST_EXTENDS/pts-trondheim.wav null 2>&1
echo \$? > ~/test-exit-status" > lame
chmod +x lame
