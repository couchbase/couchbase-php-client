#!/usr/bin/env ruby
# frozen_string_literal: true

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

require "English"
require "fileutils"
require "rbconfig"
require "shellwords"
require "optparse"

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

def which(name)
  ENV.fetch("PATH", "")
     .split(File::PATH_SEPARATOR)
     .map { |path| [path, name].join(File::SEPARATOR) + RbConfig::CONFIG["EXEEXT"] }
     .find { |file| File.executable?(file) }
end

def windows?
  RbConfig::CONFIG["target_os"].match?(/mingw/)
end

CB_PHP_EXECUTABLE =
  if windows?
    which("php")
  else
    ENV.fetch("CB_PHP_EXECUTABLE", File.join(CB_PHP_PREFIX, "bin", CB_PHP_NAME))
  end

def run(*args)
  puts
  args.flatten.compact!
  pp args
  system(*args) || abort("command returned non-zero status (#{$CHILD_STATUS}): #{args.join(' ')}")
end

def capture(*args)
  puts
  args.flatten.compact!
  pp args
  command = args.join(" ")
  output = IO.popen(args, err: [:child, :out], &:read)
  abort("command returned non-zero status (#{$CHILD_STATUS}): #{command}") unless $CHILD_STATUS.success?
  output
end

def find_extension(name_pattern, project_root, module_location_candidates)
  module_names = [
    "#{name_pattern}.#{RbConfig::CONFIG['DLEXT']}",
    "#{name_pattern}.#{RbConfig::CONFIG['SOEXT']}",
    "#{name_pattern}.so",
  ].map { |name| ["php_#{name}", name] }.flatten

  module_locations = module_names.map do |name|
    [
      "#{project_root}/modules/#{name}",
      "#{project_root}/#{name}",
    ] + Dir["#{project_root}/couchbase*/**/#{name}"]
  end.flatten.sort.uniq

  module_locations.each { |path| module_location_candidates << path }
  module_locations.find { |path| File.exist?(path) }
end

if ENV["CB_VALGRIND"]
  ENV["USE_ZEND_ALLOC"] = 0
  ENV["ZEND_DONT_UNLOAD_MODULES"] = 1
end

project_root = File.expand_path(File.join(__dir__, ".."))
build_root = File.join(project_root, "build")

caves_binary = File.join(build_root, windows? ? "gocaves.exe" : "gocaves")
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
    when /mingw/
      "gocaves-windows.exe"
    else
      abort(format('unexpected architecture, please update "%<this_file>s", your target_os="%<os>s", arch="%<arch>s"',
                   this_file: File.realpath(__FILE__),
                   os: RbConfig::CONFIG["target_os"],
                   arch: RbConfig::CONFIG["arch"]))
    end
  caves_url = "https://github.com/couchbaselabs/gocaves/releases/download/#{caves_version}/#{basename}"
  FileUtils.mkdir_p(File.dirname(caves_binary))
  run("curl -L -o #{caves_binary.shellescape} #{caves_url}")
  run("chmod a+x #{caves_binary.shellescape}") unless windows?
end

php_unit_phar = File.join(build_root, "phpunit.phar")
unless File.file?(php_unit_phar)
  php_unit_version = "10.5.38"
  php_unit_url = "https://phar.phpunit.de/phpunit-#{php_unit_version}.phar"
  FileUtils.mkdir_p(File.dirname(php_unit_phar))
  run("curl -L -o #{php_unit_phar.shellescape} #{php_unit_url}")
end

abi_versions = []

OptionParser.new do |opt|
  opt.on("-v abi", "--version abi", "Add abi") do |abi|
    abi_versions.push(abi)
  end
end.parse!

abi_versions = ["unversioned"] if abi_versions.empty?

module_location_candidates = []
extra_php_args = []

abi_versions.each do |v|
  if v == "unversioned"
    ext = find_extension("couchbase", project_root, module_location_candidates)
    extra_php_args << "-d" << "extension=#{ext}"
  else
    ext = find_extension("couchbase_" + v, project_root, module_location_candidates)
    extra_php_args << "-d" << "extension=#{ext}"
  end
end

abort "Unable to find the module. Candidates: #{module_location_candidates.inspect}" if extra_php_args.empty?

tests = ARGV.to_a
tests << File.join(project_root, "tests") if tests.empty?
results_xml = File.join(project_root, "results.xml")

logs_dir = File.join(project_root, "logs")
FileUtils.mkdir_p(logs_dir)

log_args =
  if ENV["CI"]
    [
      "-d", "couchbase.log_stderr=0",
      "-d", "couchbase.log_path=#{File.join(logs_dir, 'tests.log').shellescape}"
    ]
  else
    ["-d", "couchbase.log_stderr=1"]
  end

if (log_level = ENV.fetch("TEST_LOG_LEVEL", nil))
  log_args << "-d" << "couchbase.log_level=#{log_level}"
end

if windows?
  extra_php_args << "-d" << "extension=sockets"
  extra_php_args << "-d" << "extension=mbstring"
end

Dir.chdir(project_root) do
  run(CB_PHP_EXECUTABLE, *extra_php_args, "-m")
  puts capture(CB_PHP_EXECUTABLE, *extra_php_args, "-i")
    .split("\n")
    .grep(/couchbase/i)
    .join("\n")
  run(CB_PHP_EXECUTABLE,
      *extra_php_args,
      "-d", "couchbase.log_php_log_err=0",
      *log_args,
      php_unit_phar,
      "--color",
      "--display-warnings",
      "--testdox",
      *tests,
      "--log-junit", results_xml)
end
