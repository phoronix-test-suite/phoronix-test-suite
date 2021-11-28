#!/bin/sh

tar -xf sample-photo-6000x4000-jxl-1.tar.xz

echo "#!/bin/sh
\$TEST_EXTENDS/libjxl-0.6.1/build/tools/djxl sample-photo-6000x4000.jxl out.png \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > jpegxl-decode
chmod +x jpegxl-decode
