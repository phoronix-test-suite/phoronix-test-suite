#!/bin/bash
pip3 install --user tensorflow-cpu==2.20.0
# horovod fails to build by gcc 13+, using "independent" performance runs
# HOROVOD_WITH_TENSORFLOW=1 pip3 install --no-cache-dir horovod
echo $? > ~/install-exit-status
unzip -o tensorflow-benchmarks-8fb79079265933ad39bea628b777af662f5bf15d.zip
# Compatibility workarounds for TensorFlow 2.16...
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i 's/use_tf_layers = use_tf_layers/use_tf_layers = False/g' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/convnet_builder.py
cat <<'EOF' > tensorflow
#!/bin/bash

# OpenMPI variables
# --allow-run-as-root is not supported by MPICH and Intel MPI
export OMPI_ALLOW_RUN_AS_ROOT=1
export OMPI_ALLOW_RUN_AS_ROOT_CONFIRM=1
# These args are ignored by MPICH and Intel MPI so safe to pass to mpirun
export OMPI_MORE_ARGS="--map-by numa --bind-to numa"
# Intel MPI variables
export I_MPI_PIN_CELL=unit
export I_MPI_PIN_DOMAIN=numa

cd benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/

# By default TF_BENCH_RANK_COUNT is NUMA count
TF_BENCH_RANK_COUNT=${TF_BENCH_RANK_COUNT:=`numactl -H 2>/dev/null| grep -c -E "cpus: [0-9]"`}
if [ "$3" == "gpu" ] || [ $TF_BENCH_RANK_COUNT -lt 1 ]; then TF_BENCH_RANK_COUNT=1; fi  # GPU / no NUMA / no numactl / incorect external TF_BENCH_RANK_COUNT value
mpirun -n $TF_BENCH_RANK_COUNT $OMPI_MORE_ARGS python3 tf_cnn_benchmarks.py --variable_update=independent --num_batches=50 $@ > $LOG_FILE 2>&1
echo $? > ~/test-exit-status
# PTS harness reads last value by default so put aggregated results to the full $LOG_FILE for debug purposes
grep -oP 'total images/sec: [0-9.]+' $LOG_FILE | awk '{sum += $3; print $3} END {printf "total images/sec: %f\n", sum}' >> $LOG_FILE
EOF
chmod +x tensorflow