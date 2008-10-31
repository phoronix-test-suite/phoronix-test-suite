#!/bin/sh

unzip -o VolumetricFogging2.zip

echo "#!/bin/sh
cd VolumetricFogging2/
wine-humus-run VolumetricFogging2.exe" > wine-vf2
chmod +x wine-vf2
