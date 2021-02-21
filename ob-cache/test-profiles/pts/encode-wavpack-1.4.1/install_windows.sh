#!/bin/sh

unzip -o wavpack-5.3.0-x64.zip
cp $TEST_EXTENDS/pts-trondheim.wav pts-trondheim.wav
chmod +x wavpack.exe

echo "#!/bin/sh
./wavpack.exe -q -r -hhx3 -y pts-trondheim.wav
echo \$? > ~/test-exit-status" > encode-wavpack
chmod +x encode-wavpack
