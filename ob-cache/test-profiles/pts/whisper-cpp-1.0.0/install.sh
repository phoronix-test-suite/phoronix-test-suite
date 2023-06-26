#!/bin/bash
ffmpeg -i barackobamasou2016ARXE.mp3  -acodec pcm_s16le -ac 1 -ar 16000 2016-state-of-the-union.wav
tar -xf whisper.cpp-1.4.0.tar.gz
cd whisper.cpp-1.4.0/
bash ./models/download-ggml-model.sh base.en
bash ./models/download-ggml-model.sh small.en
bash ./models/download-ggml-model.sh medium.en
make -j
echo $? > ~/install-exit-status
echo "#!/bin/sh
cd whisper.cpp-1.4.0/
./main \$@ -t \$NUM_CPU_PHYSICAL_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/whisper-cpp
chmod +x ~/whisper-cpp
