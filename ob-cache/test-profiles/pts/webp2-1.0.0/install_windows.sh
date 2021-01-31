#!/bin/sh

unzip -o libwebp2-win64-20210126.zip
unzip -o sample-photo-6000x4000-1.zip

echo "#!/bin/sh
./cwp2.exe sample-photo-6000x4000.JPG -o out.webp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > webp2
chmod +x webp2
