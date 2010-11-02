#!/bin/sh

chmod +x Unigine_Tropics-1.3.run
./Unigine_Tropics-1.3.run

echo "#!/bin/sh
cd tropics/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Tropics -video_app opengl -data_path ../ -sound_app null -system_script tropics/unigine.cpp -engine_config ../data/unigine.cfg -video_mode -1 -video_fullscreen 1 -video_multisample 0 -extern_define PHORONIX \$@ > \$LOG_FILE 2>&1" > unigine-tropics
chmod +x unigine-tropics

