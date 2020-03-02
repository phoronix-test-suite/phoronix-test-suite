#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/287390

echo '#!/bin/sh
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Metro\ Last\ Light\ Redux

HOME=$DEBUG_REAL_HOME ./metro -benchmark benchmarks\\\\benchmark -bench_runs 1 -output_file $LOG_FILE -close_on_finish' > metroll-redux
chmod +x metroll-redux
