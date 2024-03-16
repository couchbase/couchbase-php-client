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
require "shellwords"

DEFAULT_PHP_NAME = "php"
CB_PHP_NAME = ENV.fetch("CB_PHP_NAME", DEFAULT_PHP_NAME)

DEFAULT_PHP_PREFIX =
  case RbConfig::CONFIG["target_os"]
  when /darwin/
    `brew --prefix php 2>/dev/null`.strip
  else
    "/usr"
  end

CB_PHP_PREFIX = ENV.fetch("CB_PHP_PREFIX", DEFAULT_PHP_PREFIX)
CB_PHP_EXECUTABLE = ENV.fetch("CB_PHP_EXECUTABLE", File.join(CB_PHP_PREFIX, "bin", CB_PHP_NAME))

def which(name)
  ENV.fetch("PATH", "")
     .split(File::PATH_SEPARATOR)
     .map { |path| [path, name].join(File::SEPARATOR) + RbConfig::CONFIG["EXEEXT"] }
     .find { |file| File.executable?(file) }
end

def run(*args)
  args = args.compact.map(&:to_s)
  puts args.join(" ")
  system(*args) || abort("command returned non-zero status (#{$?}): #{args.join(" ")}")
end

project_root = File.expand_path(File.join(__dir__, ".."))
build_root = File.join(project_root, "build")

caves_binary = File.join(build_root, "gocaves")
unless File.file?(caves_binary)
  caves_version = "v0.0.1-78"
  basename =
    case RbConfig::CONFIG["target_os"]
    when /darwin/
      case RUBY_PLATFORM
      when /arm64/
        "gocaves-macos-arm64"
      else
        "gocaves-macos"
      end
    when /linux/
      case RbConfig::CONFIG["arch"]
      when /aarch64/
        "gocaves-linux-arm64"
      else
        "gocaves-linux-amd64"
      end
    else
      abort(format("unexpected architecture, please update \"%s\", your target_os=\"%s\", arch=\"%s\"",
                   File.realpath(__FILE__), RbConfig::CONFIG["target_os"], RbConfig::CONFIG["arch"]))
    end
  caves_url = "https://github.com/couchbaselabs/gocaves/releases/download/#{caves_version}/#{basename}"
  FileUtils.mkdir_p(File.dirname(caves_binary))
  run("curl -L -o #{caves_binary.shellescape} #{caves_url}")
  run("chmod a+x #{caves_binary.shellescape}")
end

php_unit_phar = File.join(build_root, "phpunit.phar")
unless File.file?(php_unit_phar)
  php_unit_version = "9.5"
  php_unit_url = "https://phar.phpunit.de/phpunit-#{php_unit_version}.phar"
  FileUtils.mkdir_p(File.dirname(php_unit_phar))
  run("curl -L -o #{php_unit_phar.shellescape} #{php_unit_url}")
end

module_names = [
  "couchbase.#{RbConfig::CONFIG["DLEXT"]}",
  "couchbase.#{RbConfig::CONFIG["SOEXT"]}",
  "couchbase.so",
]
module_locations = module_names.map do |name|
  [
    "#{project_root}/modules/#{name}",
    "#{project_root}/#{name}",
  ] + Dir["#{project_root}/couchbase*/#{name}"]
end.flatten.sort.uniq

couchbase_ext = module_locations.find { |path| File.exist?(path) }
if couchbase_ext
  puts "Found module: #{couchbase_ext}"
else
  abort "Unable to find the module. Candidates: #{module_locations.inspect}"
end

tests = ARGV.to_a
tests << File.join(project_root, "tests") if tests.empty?
results_xml = File.join(project_root, "results.xml")

Dir.chdir(project_root) do
  run("#{CB_PHP_EXECUTABLE} -d extension=#{couchbase_ext} -m")
  run("#{CB_PHP_EXECUTABLE} -d extension=#{couchbase_ext} -i | grep couchbase")
  run("#{CB_PHP_EXECUTABLE} -d extension=#{couchbase_ext} -d couchbase.log_stderr=1 -d cuchbase.log_php_log_err=0 #{php_unit_phar.shellescape} --color --testdox #{tests.map(&:shellescape).join(' ')} --log-junit #{results_xml}")
end
