#!/bin/sh

echo "#!/bin/bash
[ ! -d \"/Volumes/MAXON\ Cinebench\" ] && hdid CinebenchR20.dmg
/Volumes/MAXON\ Cinebench/Cinebench.app/Contents/MacOS/Cinebench g_acceptDisclaimer=true \$@ > \$LOG_FILE" > ~/cinebench
