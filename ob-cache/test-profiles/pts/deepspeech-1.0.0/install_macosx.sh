#!/bin/sh

tar -xf deepspeech-0.6.0-models.tar.gz
tar -xf sample-speech-1.tar.xz
tar -xf native_client.amd64.cpu.osx.tar.xz

echo "#!/bin/sh
./deepspeech --model deepspeech-0.6.0-models/output_graph.pbmm --lm deepspeech-0.6.0-models/lm.binary --trie deepspeech-0.6.0-models/trie  --audio sample-speech-1.wav -t > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > deepspeech-run
chmod +x deepspeech-run
