#!/bin/sh
unzip -o jpeg-test-1.zip
unzip -o libjpeg-turbo64-1.5.3-win64-vc-1.zip

echo "#!/bin/sh
cd libjpeg-turbo64/bin/
./tjbench.exe ../../jpeg-test-1.JPG -nowrite > \$LOG_FILE" > tjbench
chmod +x tjbench
