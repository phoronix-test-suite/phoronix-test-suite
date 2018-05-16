
# Phoronix Test Suite On Windows

### Introduction
Phoronix Test Suite 8.0 features rewritten Windows support that is at a near feature parity to the program's long-standing support for Linux, macOS, BSD and Solaris operating systems. To make it abundantly clear, if you are using a Phoronix Test Suite version pre-8.0, you are best upgrading or ideally using Phoronix Test Suite Git as the Windows support remains in very active development at the moment as of early 2018.
The Phoronix Test Suite Windows support currently targets **Windows 10 x64** and **Windows Server 2016 x64** . Earlier versions of Windows, namely Windows Server 2012 and Windows 8, may work to some extent but some hardware/software reporting features and other capabilities may be missing or report warning messages. The Phoronix Test Suite Windows support is also exclusively focused on x86 64-bit support: the Phoronix Test Suite itself will run on x86 32-bit but many of the program dependencies are configured for making use of 64-bit binaries.

### Windows Setup / Dependencies
As with Phoronix Test Suite on Linux and other operating systems, the principal dependency is on PHP (PHP v5.3 or newer, including PHP 7.x). Running the *phoronix-test-suite.bat* file launcher for the Phoronix Test Suite on Windows will attempt to download and setup PHP on the system under *C:\PHP* as the default location should PHP support not be found within your system's *Program Files* directories. The PHP Windows build does depend upon Microsoft Visual C++ redistributable libraries, which the Windows launcher will also attempt to download and install if needed.
The Phoronix Test Suite on Windows does depend upon [Cygwin](https://www.cygwin.com/) for its Bash interpreter and other basic utilities to ease the process of porting test profiles to Windows with being able to use many of the same test installation scripts on Windows/Linux/macOS/BSD/Solaris then largely unmodified. Most of the Windows tests depend upon their respective native Windows applications/binaries while this Cygwin support is a convenience for handling these Bash setup scripts and also some test profiles that depend upon a GNU toolchain. The Phoronix Test Suite will attempt to download and setup Cygwin on the system if Cygwin isn't found in its default location of *C:\cygwin64* .
Various test profiles may depend upon other "external dependencies" like Python, PERL, Steam, and Java, as examples. The Phoronix Test Suite as with its support for other operating systems and Linux distributions will attempt to install these needed dependencies on a per-test basis when needed if existing support is not detected on the system.

### Running The Phoronix Test Suite On Windows
The Phoronix Test Suite can run from its local directory and does not need to be "installed" to a system path or any other "setup" process prior to execution. On a clean install of Windows 10 x64 or Windows Server 2016, deploying the Phoronix Test Suite is designed to be as easy and straight-forward as possible:
1. Download the Phoronix Test Suite 8.0+ or [Phoronix-Test-Suite from GitHub](https://github.com/phoronix-test-suite/phoronix-test-suite) ( [zip file](https://github.com/phoronix-test-suite/phoronix-test-suite/archive/master.zip) ).
2. From the Command Prompt or PowerShell, enter the *phoronix-test-suite* directory whether it be from Git or a zipped download.
3. Run the *phoronix-test-suite.bat* file that should proceed to run the Phoronix Test Suite just as you would on any other operating system. If needed the Phoronix Test Suite will try to initially download and setup PHP if needed followed by the attempted automatic Cygwin setup, etc.
4. Any of the Phoronix Test Suite commands from other operating systems should work on Windows. If you are new to the Phoronix Test Suite, you may enjoy a bit more guided experience by running the **phoronix-test-suite shell** command.

### Test Profiles On Windows
As of March 2018, around 50 of the test profiles are currently compatible with the Phoronix Test Suite on Windows. This includes many of the popular benchmarks and other interesting test cases. Over time more test profiles will continue to be ported to Windows where applicable and there are also some Windows-only tests also supported for execution by the Phoronix Test Suite.

### Getting Started
Besides **phoronix-test-suite shell** and **phoronix-test-suite help** , there is also **phoronix-test-suite interactive** for helping new users understand Phoronix Test Suite benchmarking. Long story short, it should be as easy as running **phoronix-test-suite benchmark c-ray** or **phoronix-test-suite benchmark crafty** as some examples for carrying out automated, cross-platform benchmarks in a side-by-side and fully-reproducible manner.

### Support
Community technical support is available via [GitHub](https://github.com/phoronix-test-suite/phoronix-test-suite/issues) or general inquiries via [the Phoronix Forums](https://www.phoronix.com/forums/forum/phoronix/phoronix-test-suite) . For enterprise inquiries, commercial support, and custom engineering services, [contact us](http://phoronix-test-suite.com/?k=commercial) .
