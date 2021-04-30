#!/bin/sh

unzip -o jpeg-test-1.zip
/cygdrive/c/Windows/system32/cmd.exe /c libjpeg-turbo-2.1.0-vc64.exe
cp -f jpeg-test-1.JPG /cygdrive/c/libjpeg-turbo64/bin

echo "#!/bin/sh
cd \"C:\libjpeg-turbo64\bin\"
./tjbench.exe jpeg-test-1.JPG -benchtime 20 -warmup 5 -nowrite > \$LOG_FILE" > tjbench
chmod +x tjbench
