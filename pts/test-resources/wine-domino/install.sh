#!/bin/sh

unzip -o Domino.zip

echo "#!/bin/sh
cd Domino/
wine-humus-run Domino.exe" > wine-domino
chmod +x wine-domino
