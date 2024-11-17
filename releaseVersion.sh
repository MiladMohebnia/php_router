#!/bin/bash

# Check if version argument is provided
if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

version=$1

# Update version in composer.json
jq ".version = \"${version}\"" composer.json > composer.json_temp && mv composer.json_temp composer.json

# Commit changes and create a new tag
git add composer.json
git commit -m "tagging new version ${version}"
git tag "v${version}"

# Push changes and tags to the repository
git push origin main
git push origin "v${version}"