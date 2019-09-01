#!/bin/sh

chmod +x Unigine_Sanctuary-2.3.run
./Unigine_Sanctuary-2.3.run --nox11

echo "#!/bin/sh
cd sanctuary/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH

UNIGINE_ARGS=\$@
if [[ \$UNIGINE_ARGS != *\"-video_fullscreen\"* ]]
then
	UNIGINE_ARGS=\"\$UNIGINE_ARGS -video_fullscreen 1\"
fi

./bin/Sanctuary -video_app opengl -data_path ../ -sound_app null -system_script sanctuary/unigine.cpp -engine_config ../data/unigine.cfg -video_mode -1 -extern_define PHORONIX,RELEASE \$UNIGINE_ARGS > \$LOG_FILE 2>&1" > unigine-sanctuary
chmod +x unigine-sanctuary

