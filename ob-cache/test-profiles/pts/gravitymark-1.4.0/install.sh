#!/bin/sh

chmod +x GravityMark_1.44.run
echo "y" | ./GravityMark_1.44.run --quiet --target gravity-install --nox11 --noexec


echo "#!/bin/sh
cd gravity-install/bin
LD_LIBRARY_PATH=.:\$LD_LIBRARY_PATH ./GravityMark.x64 -vsync 0 -fps 1 -benchmark 1 -close 1 -fullscreen 1 -times times.txt \$@ > \$LOG_FILE 2>&1" > gravitymark
chmod +x gravitymark

