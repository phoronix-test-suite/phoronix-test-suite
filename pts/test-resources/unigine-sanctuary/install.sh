#!/bin/sh

chmod +x Unigine_Sanctuary-2.2.run
./Unigine_Sanctuary-2.2.run

echo "#!/bin/sh
cd sanctuary/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Sanctuary \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep FPS" > unigine-sanctuary
chmod +x unigine-sanctuary

