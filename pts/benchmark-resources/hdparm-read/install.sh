#!/bin/sh

cd $1

echo "#!/bin/sh\necho \"Root permission needed to run hdparm benchmark.\"\ngksudo -w hdparm \$@" > hdparm-su
chmod +x hdparm-su

