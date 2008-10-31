#!/bin/sh

unzip -o HDR.zip

echo "#!/bin/sh
cd HDR/
wine-humus-run HDR.exe" > wine-hdr
chmod +x wine-hdr
