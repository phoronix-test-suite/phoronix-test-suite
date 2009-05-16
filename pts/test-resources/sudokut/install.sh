#!/bin/sh

tar -jxvf sudokut0.4-1.tar.bz2

echo "#!/bin/sh
cd sudokut0.4/
\$TIMER_START
./sudokut-100-runs.sh > \$LOG_FILE 2>&1
\$TIMER_STOP" > sudokut
chmod +x sudokut
