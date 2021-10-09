#!/bin/sh

unzip -o openvkl-1.0.0.x86_64.windows.zip
# Windows OVKL upstream appears borked for their builds
echo "#!/bin/sh
cd openvkl-1.0.0.x86_64.windows/bin/
LD_LIBRARY_PATH=../lib:\$LD_LIBRARY_PATH ./\$@ > \$LOG_FILE 2>&1" > openvkl
chmod +x openvkl
