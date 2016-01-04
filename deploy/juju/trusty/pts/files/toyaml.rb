#!/usr/bin/env ruby
# Author: Peter M. Petrakis <peter.petrakis@canonical.com>
require 'rubygems'
require 'nokogiri'
require 'active_support' # for enhanced Hash
# http://dirk.net/2010/08/05/convert-between-xml-hash-yaml-json-in-ruby-conversion-cheat-sheet/
# We can convert to JSON, YAML, whatever.

def return_hash()
  pts_xml = 'dotfile-phoronix-test-suite/user-config.xml'
  pts_array = Array.new
  File.open(pts_xml).each { |x| pts_array.push(x) }
  my_hash = Hash.from_xml(pts_array.to_s)
  return my_hash
end

if __FILE__ == $PROGRAM_NAME
  my_hash = return_hash()
  File.open('/tmp/yaml-out', 'w') do |fd|
    my_hash.to_yaml.each { |x| fd.puts(x) }
  end
end

# vim:ts=2:sw=2:et:ft=ruby:
