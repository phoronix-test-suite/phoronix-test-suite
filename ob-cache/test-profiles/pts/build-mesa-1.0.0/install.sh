#!/bin/sh

echo "#!/bin/sh

cd mesa-21.0.0
ninja -C build
echo \$? > ~/test-exit-status" > build-mesa

chmod +x build-mesa
