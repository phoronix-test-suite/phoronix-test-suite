#!/bin/sh

chmod +x Unigine_Tropics-1.2.run
./Unigine_Tropics-1.2.run

echo "#!/bin/sh
cd tropics/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/Tropics \$@ > \$LOG_FILE 2>&1" > unigine-tropics
chmod +x unigine-tropics

