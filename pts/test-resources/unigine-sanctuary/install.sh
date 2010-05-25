#!/bin/sh

chmod +x Unigine_Sanctuary-2.3.run
./Unigine_Sanctuary-2.3.run

echo "#!/bin/sh
cd sanctuary/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Sanctuary -video_app opengl -data_path ../ -sound_app null -system_script sanctuary/unigine.cpp -engine_config ../data/unigine.cfg -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE 2>&1" > unigine-sanctuary
chmod +x unigine-sanctuary

