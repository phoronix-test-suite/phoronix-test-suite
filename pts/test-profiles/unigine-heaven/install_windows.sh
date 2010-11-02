#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Unigine\Heaven\"
Heaven.exe -video_app opengl -data_path ./ -sound_app null -engine_config data/heaven_2.1.cfg -system_script heaven/unigine.cpp -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE" > unigine-heaven

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\Heaven
msiexec /package Unigine_Heaven-2.1.msi /passive
