#!/bin/sh

echo "#PHOROSCRIPT
export PTS_SILENT_MODE=1
\$PTS_LAUNCHER debug-self-test > \$LOG_FILE" > pts-self-test
chmod +x pts-self-test
