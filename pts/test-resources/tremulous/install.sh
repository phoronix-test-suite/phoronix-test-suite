#!/bin/sh

chmod +x tremulous-1.1.0-installer.x86.run
./tremulous-1.1.0-installer.x86.run --target tremulous_ --noexec

tar -xvf tremulous-benchmark-1.tar.gz
mv tremulous-benchmark.cfg tremulous_/base/
mv demos/ tremulous_/base/

cd tremulous_
cp bin/Linux/x86/tremulous.x86 tremulous.x86

cd ..

echo "#!/bin/sh
cd tremulous_/
./tremulous.x86 \$@ 2>&1 | grep fps
" > tremulous
chmod +x tremulous
