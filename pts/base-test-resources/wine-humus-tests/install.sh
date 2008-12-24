#!/bin/sh

echo "#!/bin/sh
\$TIMED_KILL \$1 60
export WINEDEBUG=fps
wine \$1 > \$LOG_FILE 2>&1" > wine-humus-run
chmod +x wine-humus-run
