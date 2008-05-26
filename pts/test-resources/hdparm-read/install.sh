#!/bin/sh

echo "#!/bin/sh
echo \"Root permission needed to run hdparm benchmark.\"
gksudo -w hdparm \$@" > hdparm-su
chmod +x hdparm-su

