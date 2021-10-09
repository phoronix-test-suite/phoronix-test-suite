#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/287390

echo '#!/bin/sh
xrandr -s $1x$2
sleep 3
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Metro\ Last\ Light\ Redux
# GLIBC_TUNABLES workaround per https://bugs.freedesktop.org/show_bug.cgi?id=95329
GLIBC_TUNABLES=glibc.malloc.check=3 HOME=$DEBUG_REAL_HOME ./metro -benchmark benchmarks\\\\benchmark -bench_runs 1 -output_file $LOG_FILE -close_on_finish
xrandr -s 0
sleep 3' > metroll-redux
chmod +x metroll-redux

