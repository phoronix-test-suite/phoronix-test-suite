#!/bin/sh

unzip -o appleseed-2.0.0-beta-0-g5cff7b96b-mac64-clang.zip
unzip -o emily_1.4.zip
unzip -o disney_material_1.2.zip
unzip -o material_tester_1.4.zip

cp -va emily/* appleseed
cp -va disney_material/* appleseed
cp -va material_tester/* appleseed

echo "#!/bin/bash
cd appleseed
./bin/appleseed.cli --benchmark-mode \$@ > \$LOG_FILE 2>&1" > appleseed-benchmark
chmod +x appleseed-benchmark
