#!/bin/sh

unzip -o Cloth.zip

echo "#!/bin/sh
cd Cloth/
wine-humus-run Cloth.exe" > wine-cloth
chmod +x wine-cloth
