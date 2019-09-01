#!/bin/sh

if which gpg >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: GnuPG is not found on the system!"
	echo 2 > ~/install-exit-status
fi

gunzip linux-4.3.tar.gz

echo pts-1234567890 > passphrase

echo "#!/bin/sh
gpg -c --no-options --batch --passphrase-file passphrase -o /dev/null linux-4.3.tar 2> /dev/null" > gnupg
chmod +x gnupg
