#!/bin/sh

echo "#!/bin/sh
rm -rf /cygdrive/c/*sers/*/Superposition/automation/*
cd \"C:\Program Files\Unigine\Superposition Benchmark\bin\"
./Superposition.exe -sound_app openal  -system_script superposition/system_script.cpp  -data_path ../ -engine_config ../data/superposition/unigine.cfg  -video_mode -1 -project_name Superposition  -video_resizable 1  -console_command \"config_readonly 1 && world_load superposition/superposition\" -mode 2 -preset 0 \$@

# *sers since Users is capital on Windows but under wine is users
cat /cygdrive/c/*sers/*/Superposition/automation/* > \$LOG_FILE" > unigine-super

# This assumes you will install to the default location
# C:\Program Files (x86)\Unigine\
msiexec /package Unigine_Superposition-1.0.exe /passive

