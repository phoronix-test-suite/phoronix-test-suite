#!/bin/sh

echo "#!/bin/sh
cd 'C:\Program Files (x86)\Unigine\Tropics\'
./Tropics.exe -video_app opengl -data_path ./ -sound_app null -engine_config data/unigine.cfg -system_script tropics/unigine.cpp -video_mode -1 -extern_define PHORONIX \$@ > \$LOG_FILE" > unigine-tropics

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\Tropics
msiexec /package Unigine_Tropics-1.3.msi /passive

