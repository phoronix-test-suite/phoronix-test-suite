#!/bin/sh

unzip -o libwebp-1.1.0-windows-x64.zip
unzip -o sample-photo-6000x4000-1.zip


echo "#!/bin/sh
./libwebp-1.1.0-windows-x64/bin/cwebp.exe sample-photo-6000x4000.JPG -o out.webp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > webp
chmod +x webp
