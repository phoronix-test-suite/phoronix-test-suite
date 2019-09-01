#!/bin/bash

tar -xjf zlib-1.2.7.tar.bz2
cp zlib-1.2.7/examples/zpipe.c .
gcc zpipe.c -o zpipe -lz

gunzip qt-everywhere-opensource-src-5.0.0.tar.gz
./zpipe < qt-everywhere-opensource-src-5.0.0.tar > qt-5.0.0.tar.zpipe

cat > system-decompress-zlib << EOT
#!/bin/sh

ST=\`date +%s.%N\`
./zpipe -d < qt-5.0.0.tar.zpipe > /dev/null 2>&1
ET=\`date +%s.%N\`

echo "(\$ET - \$ST) * 1000" | bc > \$LOG_FILE
EOT

chmod +x system-decompress-zlib
