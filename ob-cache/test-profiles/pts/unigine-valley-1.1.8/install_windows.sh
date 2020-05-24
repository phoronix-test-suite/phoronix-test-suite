#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Unigine\Valley Benchmark 1.0\bin\"
./Valley.exe -data_path ../ -sound_app null -engine_config ../data/valley_1.0.cfg -system_script valley/unigine.cpp -video_mode -1 -extern_define PHORONIX,RELEASE \$@ > \$LOG_FILE" > unigine-valley

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\
./Unigine_Valley-1.0.exe /passive

