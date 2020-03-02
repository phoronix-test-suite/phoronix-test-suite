#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Unigine\Heaven Benchmark 4.0\bin\"
./Heaven.exe -video_app opengl -data_path ../ -sound_app null -engine_config ../data/heaven_4.0.cfg -system_script heaven/unigine.cpp -video_mode -1 -extern_define PHORONIX,RELEASE \$@ > \$LOG_FILE" > unigine-heaven

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\
msiexec /package Unigine_Heaven-4.0.exe /passive

