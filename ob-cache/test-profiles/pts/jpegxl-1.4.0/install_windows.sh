#!/bin/sh

unzip -o jxl-x64-windows-static-061.zip
tar -xf png-samples-1.tar.xz
unzip -o sample-photo-6000x4000-1.zip

chmod +x *.exe

echo "#!/bin/sh
./cjxl.exe \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > jpegxl
chmod +x jpegxl
