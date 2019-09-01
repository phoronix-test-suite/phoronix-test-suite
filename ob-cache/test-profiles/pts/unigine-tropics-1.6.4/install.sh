#!/bin/sh

chmod +x Unigine_Tropics-1.3.run
./Unigine_Tropics-1.3.run --nox11

echo "#!/bin/sh
cd tropics/

UNIGINE_ARGS=\$@
if [[ \$UNIGINE_ARGS != *\"-video_fullscreen\"* ]]
then
	UNIGINE_ARGS=\"\$UNIGINE_ARGS -video_fullscreen 1\"
fi

export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Tropics -video_app opengl -data_path ../ -sound_app null -system_script tropics/unigine.cpp -engine_config ../data/unigine.cfg -video_mode -1 -video_multisample 0 -extern_define PHORONIX,RELEASE \$UNIGINE_ARGS > \$LOG_FILE 2>&1" > unigine-tropics
chmod +x unigine-tropics

