#!/bin/sh

pip3 install --user vosk==0.3.21
echo $? > ~/install-exit-status

tar -xf sample-speech-1.tar.xz
tar -xf vosk-api-0.3.21.tar.gz
cd vosk-api-0.3.21/python/example
unzip -o ~/vosk-model-en-us-daanzu-20200905.zip
mv vosk-model-en-us-daanzu-20200905 model

cd ~

echo "#!/bin/sh
cd vosk-api-0.3.21/python/example
python3 ./test_simple.py ~/sample-speech-1.wav
echo \$? > ~/test-exit-status" > vosk
chmod +x vosk
