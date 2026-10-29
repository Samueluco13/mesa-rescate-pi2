#!/bin/sh
set -eu
project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$project_dir"
npx --yes @wp-playground/cli@3.1.61 server --php=8.3 --wp=6.8 --port=9400 --workers=2 --login --mount="$project_dir/plugin/rescate-alimentos:/wordpress/wp-content/plugins/rescate-alimentos" --mount="$project_dir/theme/rescate-mobile:/wordpress/wp-content/themes/rescate-mobile" --mount="$project_dir/tests:/qa" --blueprint="$project_dir/dev/blueprint.json"
