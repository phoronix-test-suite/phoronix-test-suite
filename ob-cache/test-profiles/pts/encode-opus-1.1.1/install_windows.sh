#!/bin/sh

unzip -o opus-tools-0.2-opus-1.3.1.zip
chmod +x opusenc.exe

echo "#!/bin/sh
./opusenc.exe 2L38_01_192kHz.flac opus-sample.opus
echo \$? > ~/test-exit-status" > encode-opus
chmod +x encode-opus
