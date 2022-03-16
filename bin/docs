#!/usr/bin/ruby

require "fileutils"
require "shellwords"

DEFAULT_PHP_PREFIX =
  case RbConfig::CONFIG["target_os"]
  when /darwin/
    `brew --prefix php 2>/dev/null`.strip
  else
    "/usr"
  end

CB_PHP_PREFIX = ENV.fetch("CB_PHP_PREFIX", DEFAULT_PHP_PREFIX)

def run(*args)
  args = args.compact.map(&:to_s)
  puts args.join(" ")
  system(*args) || abort("command returned non-zero status: #{args.join(" ")}")
end

project_root = File.expand_path(File.join(__dir__, ".."))
build_root = File.join(project_root, "build")
php_documentor_phar = File.join(build_root, "phpdoc.phar")

unless File.file?(php_documentor_phar)
  php_documentor_version = "3.3.0"
  php_documentor_url = "https://github.com/phpDocumentor/phpDocumentor/releases/download/v#{php_documentor_version}/phpDocumentor.phar"
  FileUtils.mkdir_p(File.dirname(php_documentor_phar))
  run("curl -L -o #{php_documentor_phar.shellescape} #{php_documentor_url}")
end

php_executable = File.join(CB_PHP_PREFIX, "bin", "php")
sdk_version = ENV.fetch("CB_VERSION") do 
  default_version = "0.0.0"
  git_version = `git describe`.strip.sub(/^v/, '') rescue default_version
  git_version.empty? ? default_version : git_version
end

output_directory = File.join(build_root, "couchbase-php-client-#{sdk_version}")
Dir.chdir(project_root) do 
  run("#{php_executable} #{php_documentor_phar.shellescape} --force --validate --sourcecode --target #{output_directory.shellescape} --directory #{File.join(project_root, "Couchbase").shellescape}")
end

puts "firefox #{File.join(output_directory, "index.html")}"
