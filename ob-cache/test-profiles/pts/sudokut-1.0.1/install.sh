#!/bin/sh

tar -jxvf sudokut0.4-1.tar.bz2

echo "#!/bin/sh
cd sudokut0.4/
./sudokut-100-runs.sh > \$LOG_FILE 2>&1" > sudokut
chmod +x sudokut
