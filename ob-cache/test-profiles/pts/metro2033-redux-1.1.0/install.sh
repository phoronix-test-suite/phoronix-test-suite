#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/286690

echo '#!/bin/sh
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Metro\ 2033\ Redux

HOME=$DEBUG_REAL_HOME ./metro -benchmark benchmarks\\\\benchmark33 -bench_runs 1 -output_file $LOG_FILE -close_on_finish' > metro2033-redux
chmod +x metro2033-redux
