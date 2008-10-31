#!/bin/sh

unzip -o Fire2.zip

echo "#!/bin/sh
cd Fire2/
wine-humus-run Fire2.exe" > wine-fire2
chmod +x wine-fire2
