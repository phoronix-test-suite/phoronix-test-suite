#!/bin/sh
chmod +x build2-install-0.13.0.sh

echo "#!/bin/sh

# Ensure clean
rm -rf build2-toolchain-0.13.0
rm -rf t
mkdir t

# Build
./build2-install-0.13.0.sh --local --yes --no-check t
echo \$? > ~/test-exit-status" > build2

chmod +x build2
