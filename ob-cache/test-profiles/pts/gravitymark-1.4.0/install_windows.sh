#!/bin/bash

echo "#!/bin/bash
cd \"C:\Program Files\GravityMark\bin\"
./GravityMark.exe -vsync 0 -fps 1 -benchmark 1 -close 1 -fullscreen 1 -times times.txt \$@ > \$LOG_FILE" > gravitymark

/cygdrive/c/Windows/system32/msiexec.exe /package GravityMark_1.44.msi /quiet /passive
