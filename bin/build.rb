#!/usr/bin/env ruby

#    Copyright 2020-Present Couchbase, Inc.
#
#  Licensed under the Apache License, Version 2.0 (the "License");
#  you may not use this file except in compliance with the License.
#  You may obtain a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
#  Unless required by applicable law or agreed to in writing, software
#  distributed under the License is distributed on an "AS IS" BASIS,
#  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#  See the License for the specific language governing permissions and
#  limitations under the License.

require "fileutils"
require "rbconfig"

def echo_env(*var_names)
  var_names.each do |name|
    value = ENV[name]
    puts "#{name}=#{value}" if value && !value.empty?
  end
end

echo_env("HOSTNAME", "NODE_NAME", "CONTAINER_TAG", "JENKINS_SLAVE_LABELS", "NODE_LABELS")

def which(name)
  ENV.fetch("PATH", "")
     .split(File::PATH_SEPARATOR)
     .map { |path| [path, name].join(File::SEPARATOR) + RbConfig::CONFIG["EXEEXT"] }
     .find { |file| File.executable?(file) }
end

def run(*args)
  args = args.compact.map(&:to_s)
  puts args.join(" ")
  system(*args) || abort("command returned non-zero status: #{args.join(" ")}")
end

PROJECT_ROOT = File.realpath(File.join(__dir__, '..'))

DEFAULT_PHP_PREFIX =
  case RbConfig::CONFIG["target_os"]
  when /darwin/
    `brew --prefix php 2>/dev/null`.strip
  else
    "/usr"
  end

default_cc="cc"
default_cxx="c++"
case RbConfig::CONFIG["target_os"]
when /darwin/
  default_cc = "/usr/bin/gcc"
  default_cxx = "/usr/bin/g++"
when /linux/
  prefix = "/opt/rh/devtoolset-9/root/usr"
  if File.directory?(prefix)
    default_cc = File.join(prefix, "bin/gcc")
    default_cxx = File.join(prefix, "bin/g++")
  end
end

CB_PHP_PREFIX = ENV.fetch("CB_PHP_PREFIX", DEFAULT_PHP_PREFIX)
CB_CC = ENV.fetch("CB_CC", default_cc)
CB_CXX = ENV.fetch("CB_CXX", default_cxx)
CB_CMAKE_BUILD_TYPE = ENV.fetch("CMAKE_BUILD_TYPE", "Debug")

run("#{CB_PHP_PREFIX}/bin/php --version || true")
run("#{CB_PHP_PREFIX}/bin/php --ini || true")
run("#{CB_PHP_PREFIX}/bin/php-config || true")

LOCAL_OPENSSL="/usr/local/openssl"
CB_OPENSSL_ROOT = ENV.fetch("CB_OPENSSL_ROOT", File.directory?(LOCAL_OPENSSL) ? LOCAL_OPENSSL : nil)
if CB_OPENSSL_ROOT
  ENV["COUCHBASE_CMAKE_EXTRA"] = "-DOPENSSL_ROOT_DIR=#{CB_OPENSSL_ROOT}"
end

Dir.chdir(PROJECT_ROOT) do
  run("#{CB_PHP_PREFIX}/bin/phpize")
  run("rm -rf cmake-build") unless ENV.fetch("CB_DO_NOT_CLEAN", false)
  run("./configure --with-php-config=#{CB_PHP_PREFIX}/bin/php-config CC=#{CB_CC} CXX=#{CB_CXX} COUCHBASE_CMAKE_BUILD_TYPE=#{CB_CMAKE_BUILD_TYPE}")
  exit if ENV.fetch("CB_CONFIGURE_ONLY", false)
  run("make clean") unless ENV.fetch("CB_DO_NOT_CLEAN", false)
  ENV["CMAKE_BUILD_PARALLEL_LEVEL"] = ENV.fetch("CB_NUMBER_OF_JOBS", "4")
  run("make V=1")
end

COUCHBASE_EXT = "#{PROJECT_ROOT}/modules/couchbase.#{RbConfig::CONFIG["SOEXT"]}"
unless File.exist?(COUCHBASE_EXT)
  alt_filename = "#{PROJECT_ROOT}/modules/couchbase.so"
  if File.exist?(alt_filename)
    COUCHBASE_EXT = alt_filename
  end
end

run("#{CB_PHP_PREFIX}/bin/php -d extension=#{COUCHBASE_EXT} -m | grep couchbase")
run("#{CB_PHP_PREFIX}/bin/php -d extension=#{COUCHBASE_EXT} -i | grep couchbase")

File.write("#{PROJECT_ROOT}/build/try_to_load.php", <<EOF)
<?php
print_r(\\Couchbase\\Extension\\version());

require_once 'Couchbase/autoload.php';
var_dump((new ReflectionClass('\\\\Couchbase\\\\Cluster'))->getFileName());
EOF

run("#{CB_PHP_PREFIX}/bin/php -d extension=#{COUCHBASE_EXT} #{PROJECT_ROOT}/build/try_to_load.php")
