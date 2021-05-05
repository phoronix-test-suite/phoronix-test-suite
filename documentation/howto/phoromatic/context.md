

# Using Phoromatic Context Switching

This howto describes to use the Phoromatic lifecycle concept of context to allow automated switching of Configurations under Test with no manual intervention.

## Who needs Context Switching

Context would be used by Phoromatic users that would like to do automated testing of systems while varying features like BIOS, kernel, overclocking features, or compilers.  Phoromatic treats Context as an opaque string with no particular meaning to Phoronix Test Suite. 

## Recommended Background Reading

This howto assumes an understanding of the [Phoromatic Test Lifecycle](./theory-of-operation.md) and uses key terms found in the [Glossary](./glossary.md).

## Pre-requisites

This howto assumes that you have
  - Installed and configured [Phoronix test Suite](./installing.md)
  - Have a [running Phoromatic Server](./phoromatic.md) with a user that is logged in.
  - Have a PTS client that is registered with Phoromatic, and is configured to reconnect on boot.
  - Have a PTS client Debian deriviative that has the update-alternatives

## Scenario

In this scenario our CUT will be differing compilers through gcc versions.  The steps we will take are to 
1. Configure our system to easily switch compilers
2. Create a simple context switching script
3. Trigger a set of benchmarks that exercise different compilers by running the tests under different contexts
4. Compare the results

### Configure Compilers

For this example we will install as many compilers as we can (assuming compatilbility). 

```
$ sudo apt -y install gcc-4.9 g++-4.9 gcc-5 g++-5 gcc-6 g++-6 gcc-7 g++-7 gcc-8 g++-8
```

Then for each of these we will configure them into the alternatives system

```
$ sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.9 4
$ sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-5 5
...
$ sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-7 7
$ sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-8 8
```

We can then confirm that the list of compilers are configured as alternatives

```
$ sudo update-alternatives --list gcc
/usr/bin/gcc-4.9
...
/usr/bin/gcc-8
```

Finally, we can confirm that the different compilers are easily switched.

```
$ sudo update-alternatives --set gcc /usr/bin/gcc-6
$ gcc --version
gcc (Raspbian 6.5.0-1+rpi1+b1) 6.5.0 20181026
Copyright (C) 2017 Free Software Foundation, Inc.
This is free software; see the source for copying conditions.  There is NO
warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
$ sudo update-alternatives --set gcc /usr/bin/gcc-8
$ gcc --version
gcc (Raspbian 8.3.0-6+rpi1) 8.3.0
Copyright (C) 2018 Free Software Foundation, Inc.
This is free software; see the source for copying conditions.  There is NO
warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
```

### Create a test Context Switch

The role of context scripts are to ensure that the system is in a requested context, or report that the system cannot switch into that state.  To achieve this, every context script should take the following steps

1. Determine that the context is valid
2. Determine the current context
3. Switch the context if needed
4. Validate that the context switch has been correctly made

In the case of runtime configuration like the gcc version above this should be relatively easy to inspect and verify.  

For changes that require a reboot you can easily reboot during the context switch.  Phoromatic does not consider a test to be underway until the full pre context switch is completed.  If a PTS Client disconnects from Phoromatic during context switching, the same test and context will be provided to PTS Client upon reconnection.  This allows the context switch to happen on the first execution of the context script, and the context to be confirmed on the second.

For the purposes of this howto, we will use the `update-alternatives --query gcc` to determine context, `update-alternatives --set gcc` to set context, and will use the gcc shortname (eg: `gcc-4.9`, `gcc-5`, etc) as the context.  

Our simple script is shown below. You can test the script by passing in a context as the first parameter

```
#!/bin/sh

# extract our context from the first parameter
_context=$0

# Validate that the context is valid

# Check the current context


# If context switch is needed, switch to that context

# Validate that the new conext is in place
```


### Trigger a Set of Benchmarks

### Comapre our Results

## Alternatives

For continuous integration systems we commonly use git commit hash (eg: a1fb3a322) as a context that allows for easy integration of a git commit hook notification to be passed to a context 
