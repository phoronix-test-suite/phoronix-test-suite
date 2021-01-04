#!/bin/sh

unzip -o darktable-3.2.1-win64.zip
tar -xjvf darktable-bench-assets-1.tar.bz2
tar -xf server-rack.tar.xz
mv *.SRW  darktable-2.4.1-win64/bin/
mv *.dng  darktable-2.4.1-win64/bin/
mv *.NEF  darktable-2.4.1-win64/bin/

echo "#!/bin/sh
cd darktable-2.4.1-win64/bin/
rm -f output*.jpg
./darktable-cli.exe \$@ > \$LOG_FILE
./darktable-cli.exe --version | head -n 1 | awk '{ print \$NF }' > ~/pts-test-version" > darktable
chmod +x darktable
