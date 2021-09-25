
# Creating Custom Tests / Benchmarks (Test Profiles)

## Overview

*This test profile creation documentation is a work in progress.*

*Note: For some of the sample/debug test profiles mentioned on this page you may also need to first run the `phoronix-test-suite enable-repo debug` command to enable access to the tests on your local system.*

The easiest way to get started with creating a test at this time would be by modifying an existing test profile, such as:

> cp -va ~/.phoronix-test-suite/test-profiles/pts/build-llvm-1.3.1/ ~/.phoronix-test-suite/test-profiles/local/build-llvm/
> (Edit the test contents within ~/.phoronix-test-suite/test-profiles/local/build-llvm/ )
> ./phoronix-test-suite benchmark local/build-llvm # to try out the modified test profile

Some built-in Phoronix Test Suite commands that may help in test profile creation are `phoronix-test-suite create-test-profile` to help in the generation of the standard XML metadata for test profiles and other boilerplate code, `phoronix-test-suite debug-install [test]` to see the output of the install process, `phoronix-test-suite debug-run [test]` to debug the test run-time behavior, `phoronix-test-suite debug-result-parser [test]` if trying to debug the result parser XML handling, and `phoronix-test-suite inspect-test-profile [test]` to view the parsed Phoronix Test Suite test profile.

The `phoronix-test-suite diagnostics` output can also be beneficial for seeing the environment variables that by default are exported to all test profiles if needing to query certain software/hardware information.

## Pass/Fail Tests

The Phoronix Test Suite is primarily focused on quantitative tests, but does support pass/fail type testing (e.g. success / failure). See `phoronix-test-suite benchmark debug/pass-fail` as an example test profile for how to setup a pass/fail test. Similarly, there is `phoronix-test-suite benchmark debug/multi-pass-fail` for the multi-pass/fail of multiple pass/failures as part of a single result.

## Tests With Single Run, Multiple Results Output

See test profiles such as *pts/hpcc* and *pts/fio* for test profiles having a *results-definition.xml* where multiple results are generated from a single run.

## Sensor-Based Monitoring As A Test Result

See *pts/video-cpu-usage* as an (old) example of a working test centered around CPU usage tracking during video playback.

## Triggering Reboot During Test Install Or Test Run-Time

Since Phoronix Test Suite 10.6 is a built-in helper if a test profile during installation or at run-time needs to trigger a system reboot. This can be done by having the test install script or run script write to *~/reboot-needed*. If *~/reboot-needed* is created by the test profile, the Phoronix Test Suite will attempt to reboot the system in a cross-platform compatible manner.

The default behavior if reboot-needed is present is to reboot as soon as the test script execution finishes. This is the "immediate" mode and the default. Alternatively, if "queued" is written to the reboot-needed file, the Phoronix Test Suite will wait until after all other tests are either installed or run before triggering the reboot (or until running a test requesting an immediate reboot). The queued mode is intended to cut-down on the possible number of reboots needed, depending upon the constraints and intentions of the test profile rebooting.

When the test is recovering/re-run after a reboot, the Phoronix Test Suite will set the *$TEST_RECOVERING_FROM_REBOOT* environment variable to let the test profile know a reboot happened. Note that the $TEST_RECOVERING_FROM_REBOOT behavior will only report if it's the first subsequent run of the Phoronix Test Suite since the reboot was initiated, i.e. if there was an interruption and running PTS later on past that point of the test in question, the $TEST_RECOVERING_FROM_REBOOT would not be set. The $TEST_RECOVERING_FROM_REBOOT will also not be set if the Phoronix Test Suite client version was upgraded/changed during the reboot process.

Running `phoronix-test-suite benchmark debug/reboot-now` is a sample test profile demonstrating the reboot interface. Similarly, `phoronix-test-suite benchmark debug/reboot-during-install` demonstrates the reboot-needed activity on test installation.
