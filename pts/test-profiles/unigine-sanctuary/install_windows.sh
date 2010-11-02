#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Unigine\Sanctuary\"
Sanctuary.exe -video_app opengl -data_path ./ -sound_app null -engine_config data/unigine.cfg -system_script sanctuary/unigine.cpp -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE" > unigine-sanctuary

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\Sanctuary
msiexec /package Unigine_Sanctuary-2.3.msi /passive

