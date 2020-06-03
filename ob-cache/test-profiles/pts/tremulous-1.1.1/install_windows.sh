#!/bin/sh

unzip -o tremulous-1.1.0.zip
mv tremulous tremulous_
unzip -o tremulous-benchmark-2.zip

cp tremulous-benchmark.cfg tremulous_/base/
mkdir tremulous_/base/demos
cp demos/pts-demo.dm_69 tremulous_/base/demos/

echo "#!/bin/sh
cd tremulous_/
./tremulous.exe \$@" > tremulous
chmod +x tremulous
