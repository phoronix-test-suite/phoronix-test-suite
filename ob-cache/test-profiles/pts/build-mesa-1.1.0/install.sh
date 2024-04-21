#!/bin/sh
pip3 install --user mako
echo "#!/bin/sh
cd mesa-24.0.3
ninja -C build
echo \$? > ~/test-exit-status" > build-mesa
chmod +x build-mesa
