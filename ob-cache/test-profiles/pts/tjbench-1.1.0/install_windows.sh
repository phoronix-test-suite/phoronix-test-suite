#!/bin/sh
unzip -o jpeg-test-1.zip
./libjpeg-turbo-2.0.2-vc64.exe

echo "#!/bin/sh
cd libjpeg-turbo64/bin/
./tjbench.exe ../../jpeg-test-1.JPG -nowrite > \$LOG_FILE" > tjbench
chmod +x tjbench
