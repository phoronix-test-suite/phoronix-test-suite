#!/bin/sh


# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\Sanctuary
msiexec.exe /package Unigine_Sanctuary-2.3.msi /passive

echo "#!/bin/sh
cd 'C:\Program Files (x86)\Unigine\Sanctuary\'
./Sanctuary.exe -video_app opengl -data_path ./ -sound_app null -engine_config data/unigine.cfg -system_script sanctuary/unigine.cpp -video_mode -1 -extern_define PHORONIX \$@ > \$LOG_FILE" > unigine-sanctuary

