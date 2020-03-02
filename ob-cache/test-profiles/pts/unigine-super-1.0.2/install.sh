#!/bin/sh

chmod +x Unigine_Superposition-1.0.run
./Unigine_Superposition-1.0.run --nox11

echo "#!/bin/sh
cd Unigine_Superposition-1.0/
rm -f ~/.Superposition/automation/log*.txt
./bin/superposition  -video_app opengl -sound_app openal  -system_script superposition/system_script.cpp  -data_path ../ -engine_config ../data/superposition/unigine.cfg  -video_mode -1 -project_name Superposition  -video_resizable 1  -console_command \"config_readonly 1 && world_load superposition/superposition\" -mode 2 -preset 0 \$@ 
cat ~/.Superposition/automation/log*.txt > \$LOG_FILE" > unigine-super
chmod +x unigine-super

