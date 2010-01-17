#!/bin/sh

unzip -o tremulous-1.1.0.zip -d tremulous_/
tar -zxvf tremulous-benchmark-1.tar.gz

mv tremulous-benchmark.cfg tremulous_/tremulous/base/
mv demos/ tremulous_/tremulous/base/

echo "#!/bin/sh
cd tremulous_/tremulous/
./tremulous.x86 \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > tremulous
chmod +x tremulous
