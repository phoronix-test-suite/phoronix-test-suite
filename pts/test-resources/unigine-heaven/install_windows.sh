#!/bin/sh

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Unigine\Heaven\"
Heaven.exe -video_app opengl -data_path ./ -sound_app null -engine_config data/heaven_2.0.cfg -system_script heaven/unigine.cpp -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE" > unigine-heaven
chmod +x unigine-heaven

# This assumes you will install to the default location
Unigine_Heaven-2.0.msi

