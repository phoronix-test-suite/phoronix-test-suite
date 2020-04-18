#!/bin/sh

unzip -o openvkl-0.9.0.x86_64.macos.zip

echo "#!/bin/sh
cd openvkl-0.9.0.x86_64.macos/bin/
LD_LIBRARY_PATH=../lib:\$LD_LIBRARY_PATH ./\$@  > \$LOG_FILE 2>&1" > openvkl
chmod +x openvkl
