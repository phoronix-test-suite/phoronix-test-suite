#!/bin/sh

echo "#!/bin/sh
sleep 60
killall -9 \$1
" > humus-kill-process
chmod +x humus-kill-process

echo "#!/bin/sh
humus-kill-process \$1 &
export WINEDEBUG=fps
wine \$1 > \$LOG_FILE 2>&1" > wine-humus-run
chmod +x wine-humus-run
