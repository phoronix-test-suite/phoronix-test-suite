#!/bin/sh

cd $1
unzip -o espeak-1.37-source.zip
cd espeak-1.37-source/src/
make -j $NUM_CPU_JOBS
cd ../..

echo "#!/bin/sh
/usr/bin/time -f \"eSpeak Synthesis Time: %e Seconds\" ./espeak-1.37-source/src/espeak -f 20417-8.txt -w output.wav 2>&1
rm -f output.wav" > espeak
chmod +x espeak
