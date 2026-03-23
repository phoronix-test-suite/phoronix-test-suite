#!/bin/sh
unzip -o DiskSpd-2.2.0.zip
printf '#!/bin/sh\ncd amd64\n./diskspd.exe "$@" > "$LOG_FILE"\n' > diskspd
chmod +x diskspd
