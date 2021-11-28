#!/bin/bash

tar -xf stargate-release-21.10.9.tar.gz
unzip -o stargate-benchmark-project-1.zip

cd stargate-release-21.10.9/src

pip3 install --user -r requirements.txt

PLAT_FLAGS='-O3 -march=native' make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/bash
cd stargate-release-21.10.9/src

# Test can only handle up to 16 threads for now...
THREADCOUNT=\$((\$NUM_CPU_PHYSICAL_CORES>16?16:\$NUM_CPU_PHYSICAL_CORES))

# Based on https://github.com/stargateaudio/stargate/blob/main/docs/benchmarking.md

# Sample rate.  Normally this is 44100 or 48000, but users sometimes choose
# 96000 or 192000 for higher quality, at a much higher CPU cost.  Stargate DAW
# has been tested at rates over 1,000,000, although such rates adversely affect
# the audio by drastically changing the mix characteristics
SR=\$1
# Buffer size.  In real time audio, this affects latency, lower sizes have
# less latency, but make less efficient use of the CPU. At 44100 sample rate,
# 64 is a very low value, 512 is more normal, typical users may use 128
# to 1024.  Doubling the sample rate effectively halves the latency of this
# value, keep that in mind when changing one or the other.  Latenchy is
# calculated as (BUF_SIZE / SR) * 1000 == latency in milliseconds
BUF_SIZE=\$2
# The number of worker threads to spawn.  The limit is 16, setting to zero
# causes Stargate DAW to automatically select a very conservative value
# of 1-4 depending on the CPU that was detected
THREADS=\$THREADCOUNT
# The project folder to render.  Specifically, this is the folder that contains
# the `stargate.project` file.
PROJECT=~/stargate/projects/myproject
# The file to output.  If you want to keep all of the artifacts from this run,
# change the filename between runs
OUTFILE=test.wav
# This is the musical beat number within the project to begin rendering at.
# 0 being the first beat of the song.  It is best to get this by opening
# the project in Stargate DAW as described above, but you could also use
# arbitrary numbers.  This should be a low number, like 0 or 8
START=8
# This is the musical beat number within the project to stop rendering at
# This should always be a (much) larger number than START
END=340

./engine/stargate-engine daw \$HOME/benchmark-project \${OUTFILE?} \${START?} \${END?} \${SR?} \${BUF_SIZE?} \${THREADS?} 0 0 0 > \$LOG_FILE
echo \$? > ~/test-exit-status" > stargate
chmod +x stargate
