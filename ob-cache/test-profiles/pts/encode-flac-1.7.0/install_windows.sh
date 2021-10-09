#!/bin/sh

unzip -o flac-1.3.2-win.zip

echo "#!/bin/sh
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
./flac-1.3.2-win/win64/flac.exe --best \$TEST_EXTENDS/pts-trondheim.wav -f -o output
echo \$? > ~/test-exit-status" > encode-flac
chmod +x encode-flac
