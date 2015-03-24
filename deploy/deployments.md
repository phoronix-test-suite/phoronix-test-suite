# Pre-Scripted Phoronix Test Suite / Phoromatic Deployments

## deb-package: Generate A Debian/Ubuntu Package

Running `php deploy/deb-package/build-package-deb.php` from the main `phoronix-test-suite/` directory will generate a basic Phoronix-Test-Suite Debian package. This script depends upon `fakeroot` and `dpkg` being present on the system.

## rpm-package: Generate A RedHat/Fedora RPM Package

Running `php deploy/rpm-package/build-package-rpm.php` from the main `phoronix-test-suite/` directory will generate a basic Phoronix-Test-Suite RPM package for Red Hat / Fedora based distributions. This script depends upon `rpmbuild` being present on the system.
