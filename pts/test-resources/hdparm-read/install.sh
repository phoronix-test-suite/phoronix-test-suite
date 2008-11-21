#!/bin/sh

echo "#!/bin/sh

hdparm \$@" > hdparm-read
chmod +x hdparm-read

