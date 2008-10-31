#!/bin/sh

unzip -o MetaBalls.zip

echo "#!/bin/sh
cd MetaBalls/
wine-humus-run MetaBalls.exe" > wine-metaballs
chmod +x wine-metaballs
