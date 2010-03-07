#!/bin/sh

echo "#!/bin/sh

hdparm \$@ > \$LOG_FILE" > hdparm-read
chmod +x hdparm-read

