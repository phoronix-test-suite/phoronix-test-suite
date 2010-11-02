#!/bin/sh

unzip -o Water.zip

echo "#!/bin/sh
cd Water/
wine-humus-run Water.exe" > wine-water
chmod +x wine-water
