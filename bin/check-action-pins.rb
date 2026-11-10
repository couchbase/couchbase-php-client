#!/usr/bin/env ruby
# frozen_string_literal: true

#  Copyright 2026-Present Couchbase, Inc.
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

# Checks every `uses:` under .github:
#
# * error:
#   - a reference to another repository not pinned as
#     "owner/repo@<sha> # <tag or branch>";
#   - a step, job or service container image, or a matrix `image` or `alpine`
#     value, without a digest, and a matrix given as an expression. An image
#     given as an expression, such as `${{ matrix.image }}`, is reported as
#     unpinned too, as its value cannot be read here;
#   - a commented tag that does not point at the pinned commit;
#   - a tag or branch that could not be checked.
# * warning: a newer release tag exists, the commented branch has moved on, or
#   a pinned Docker Hub image tag now names another digest.
#
# FLOATING lists references allowed to follow a tag or branch; the reason for
# each is next to the reference.
#
# References are found with the YAML parser, so every form of a `uses:` key is
# seen; the comment is read from the line of the value. Images are read after
# aliases and merge keys resolve. A finding is reported
# once per action and pinned version, at its first reference, with the number
# of other references. Exits 1 on any error.
#
#   bin/check-action-pins.rb    # from the repository root, GH_TOKEN for gh

require "net/http"
require "open3"
require "psych"

FILES = Dir[".github/**/*.{yml,yaml}"].freeze
PINNED = /\A(?<repo>[A-Za-z0-9][A-Za-z0-9-]*\/(?!\.+[\/@])[A-Za-z0-9._-]+)(?:\/[^@\s]+)?@(?<sha>[0-9a-f]{40})\z/
VERSION = /\Av?\d+(?:\.\d+)*\z/
BRANCH = /\A(?!.*\.\.)[A-Za-z0-9_][A-Za-z0-9._\/-]*\z/
IMAGE = /\A(?:docker:\/\/)?[^@\s]+@sha256:[0-9a-f]{64}\z/
HUB_IMAGE = %r{\A(?:docker://)?(?:docker\.io/)?(?<name>[a-z0-9._-]+(?:/[a-z0-9._-]+)?):(?<tag>[^@\s]+)@(?<digest>sha256:[0-9a-f]{64})\z}
INDEX = "application/vnd.oci.image.index.v1+json, application/vnd.docker.distribution.manifest.list.v2+json"
MATRIX_IMAGE_KEYS = %w[image alpine].freeze

# Workflow file => references in it that may follow a branch instead of a pin.
FLOATING = {}.freeze

Ref = Struct.new(:file, :line, :ref, :comment)

def property(value)
  value.to_s.gsub("%", "%25").gsub("\r", "%0D").gsub("\n", "%0A").gsub(":", "%3A").gsub(",", "%2C")
end

def annotate(level, refs, title, message)
  @failed = true if level == "error"
  first = refs.first
  others = refs.size - 1
  message += " (#{others} more #{others == 1 ? 'reference' : 'references'})" if others.positive?
  puts "::#{level} file=#{property(first.file)},line=#{first.line},title=#{property(title)}::" \
       "#{message.gsub('%', '%25').gsub("\r", '%0D').gsub("\n", '%0A')}"
end

# Retried, because a failed lookup fails the check.
def gh(*args)
  error = nil
  3.times do |attempt|
    out, err, status = Open3.capture3("gh", *args)
    return [out, nil] if status.success?

    error = err.strip.lines.first.to_s.strip
    break if error.include?("HTTP 404")

    sleep(5 * (attempt + 1)) if attempt < 2
  end
  [nil, error]
end

def version(tag) = Gem::Version.new(tag.delete_prefix("v"))

# Values of `uses:` keys anywhere in the document. A key written as an alias is resolved to the
# node it names, so its value is checked like any other. An alias as the value fails the check. A
# reused anchor needs nothing more: its `uses:` node is checked where the anchor is defined.
def uses_nodes(node, found = [], anchors = {})
  anchors[node.anchor] = node if !node.is_a?(Psych::Nodes::Alias) && node.respond_to?(:anchor) && node.anchor
  case node
  when Psych::Nodes::Mapping
    node.children.each_slice(2) do |key, value|
      uses_nodes(key, found, anchors)
      key = anchors[key.anchor] if key.is_a?(Psych::Nodes::Alias)
      found << value if key.is_a?(Psych::Nodes::Scalar) && key.value == "uses"
      uses_nodes(value, found, anchors)
    end
  when Psych::Nodes::Node
    node.children&.each { |child| uses_nodes(child, found, anchors) }
  end
  found
end

def child(mapping, name)
  return unless mapping.is_a?(Psych::Nodes::Mapping)

  mapping.children.each_slice(2).find { |key, _| key.is_a?(Psych::Nodes::Scalar) && key.value == name }&.last
end

# Images of `jobs.<id>.container` and `jobs.<id>.services.<id>`, in the string
# form or as their `image:`, and the MATRIX_IMAGE_KEYS values of
# `jobs.<id>.strategy.matrix`, listed or under `include`. They are read from the
# loaded document, so aliases and merge keys resolve as YAML defines them. A
# value that is not a string, and a matrix or `include` given as an expression,
# fail the digest check.
def image_values(data)
  jobs = data.is_a?(Hash) ? data["jobs"] : nil
  return [] unless jobs.is_a?(Hash)

  jobs.values.grep(Hash).flat_map do |job|
    services = job["services"]
    containers = [job["container"]]
    containers += services.values if services.is_a?(Hash)
    strategy = job["strategy"]
    containers.compact.map { |container| container.is_a?(Hash) ? container["image"] : container }.compact +
      matrix_image_values(strategy.is_a?(Hash) ? strategy["matrix"] : nil)
  end
end

def matrix_image_values(matrix)
  return [] if matrix.nil?
  return [matrix] unless matrix.is_a?(Hash)

  entries = matrix["include"]
  return [entries] unless entries.nil? || entries.is_a?(Array)

  ([matrix] + entries.to_a).flat_map do |row|
    next [row] unless row.is_a?(Hash)

    MATRIX_IMAGE_KEYS.flat_map { |key| row[key].is_a?(Array) ? row[key] : [row[key]].compact }
  end
end

# Digest of a Docker Hub tag, read through mirror.gcr.io; nil when it cannot be read.
# After one lookup fails on the network, the rest are reported unchecked rather than retried.
$hub_unreachable = false

def hub_digest(name, tag)
  return nil if $hub_unreachable

  name = "library/#{name}" unless name.include?("/")
  uri = URI("https://mirror.gcr.io/v2/#{name}/manifests/#{tag}")
  network_error = false
  3.times do |attempt|
    begin
      response = Net::HTTP.start(uri.host, uri.port, use_ssl: true, open_timeout: 10, read_timeout: 20) do |http|
        http.head(uri.path, "Accept" => INDEX)
      end
      return response["docker-content-digest"] if response.is_a?(Net::HTTPSuccess)
      # a 404 is about this image only; a rate limit or server error may pass
      return nil unless response.is_a?(Net::HTTPTooManyRequests) || response.is_a?(Net::HTTPServerError)

      network_error = false
    rescue IOError, SocketError, SystemCallError, Timeout::Error, OpenSSL::SSL::SSLError
      network_error = true
    end
    sleep(5 * (attempt + 1)) if attempt < 2
  end
  $hub_unreachable = true if network_error
  nil
end

images = []
refs = FILES.flat_map do |file|
  lines = File.readlines(file)
  document = Psych.parse_file(file)
  image_values(Psych.safe_load(File.read(file), aliases: true)).each do |value|
    value = "(not a string)" unless value.is_a?(String)
    line = lines.index { |text| text.include?(value) }
    images << Ref.new(file, line ? line + 1 : 1, value)
  end
  uses_nodes(document).map do |node|
    # The comment after the value when it is one word, as in "# v1.2.3"; nil otherwise.
    comment = lines[node.end_line].to_s[/\A[^#]*\S\s+#\s*(\S+)\s*\z/, 1]
    Ref.new(file, node.start_line + 1, node.is_a?(Psych::Nodes::Scalar) ? node.value : "(not a string)", comment)
  end
end
# This repository's own actions and workflows, and the allowed floating references.
refs.reject! { |ref| ref.ref.start_with?("./") || FLOATING[ref.file]&.match?(ref.ref) }

docker, refs = refs.partition { |ref| ref.ref.start_with?("docker://") }
(images + docker).reject { |ref| IMAGE.match?(ref.ref) }.group_by(&:ref).each do |ref, group|
  annotate("error", group, "Unpinned image", "#{ref} must be pinned as <image>@sha256:<digest>")
end
(images + docker).select { |ref| HUB_IMAGE.match?(ref.ref) }.group_by(&:ref).each do |ref, group|
  match = HUB_IMAGE.match(ref)
  current = hub_digest(match[:name], match[:tag])
  if current.nil?
    annotate("warning", group, "Image not checked", "#{match[:name]}:#{match[:tag]}: mirror.gcr.io returned no digest")
  elsif current != match[:digest]
    annotate("warning", group, "Moved image tag", "#{match[:name]}:#{match[:tag]} is now #{current}, pinned #{match[:digest]}")
  end
end

unpinned, pinned = refs.partition do |ref|
  !PINNED.match?(ref.ref) || ref.comment.nil? || !ref.comment.match?(BRANCH)
end
unpinned.group_by(&:ref).each do |ref, group|
  annotate("error", group, "Unpinned action", "#{ref} must be pinned as owner/repo@<commit sha> # <tag or branch>")
end

pinned.group_by { |ref| PINNED.match(ref.ref)[:repo] }.each do |repo, repo_refs|
  tags = nil
  tags_error = nil
  repo_refs.group_by { |ref| [PINNED.match(ref.ref)[:sha], ref.comment] }.each do |(sha, comment), group|
    if comment.match?(VERSION)
      if tags.nil? && tags_error.nil?
        out, tags_error = gh("api", "--paginate", "repos/#{repo}/tags?per_page=100",
                             "--jq", ".[] | [.name, .commit.sha] | @tsv")
        tags = out&.lines.to_h { |line| line.chomp.split("\t", 2) }
      end
      if tags_error
        annotate("error", group, "Action not checked", "#{repo}: tags unavailable: #{tags_error}")
      elsif tags[comment].nil?
        annotate("error", group, "Unknown tag", "#{repo} has no tag #{comment}")
      elsif tags[comment] != sha
        annotate("error", group, "Moved tag", "#{repo} #{comment} points at #{tags[comment]}, pinned #{sha}")
      else
        latest = tags.keys.grep(VERSION).max_by { |tag| version(tag) }
        if version(latest) > version(comment)
          annotate("warning", group, "Newer action", "#{repo} #{latest} is available, pinned #{comment}")
        end
      end
    else
      out, err = gh("api", "repos/#{repo}/commits/#{comment}", "--jq", ".sha")
      if err
        annotate("error", group, "Action not checked", "#{repo}@#{comment}: #{err}")
      elsif out.strip != sha
        # a branch moves by design; a tag that moved is a mismatch
        _, branch_err = gh("api", "repos/#{repo}/branches/#{comment}", "--jq", ".name")
        if branch_err
          annotate("error", group, "Moved tag", "#{repo} #{comment} points at #{out.strip}, pinned #{sha}")
        else
          annotate("warning", group, "Moved branch", "#{repo} #{comment} is at #{out.strip}, pinned #{sha}")
        end
      end
    end
  end
end

exit(@failed ? 1 : 0)
