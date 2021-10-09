#!/bin/sh

unzip -o YafaRay.v3.5.1.build.standalone.Windows.MinGW-GCC7.64bit.zip
tar -xf yafarayRender-sample-1.tar.xz

echo "#!/bin/sh
./yafaray_v3/bin/yafaray-xml.exe -t \$NUM_CPU_CORES yafarayRender.xml > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > yafaray
chmod +x yafaray
