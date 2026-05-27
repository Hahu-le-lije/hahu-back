#!/usr/bin/env bash
set -euo pipefail

# Usage:
# TOKEN='4|...' BASE='https://cms-service-...' bash scripts/create_sample_packs.sh

: "${TOKEN:?Set TOKEN env var (admin Sanctum token)}"
: "${BASE:?Set BASE env var (CMS base URL)}"

echo "Using CMS: $BASE"

get_pack_id() {
  slug="$1"
  curl -s -H "Authorization: Bearer ${TOKEN}" "${BASE}/api/admin/content-packs" |
    python3 - <<PY
import sys, json
arr = json.load(sys.stdin)
for p in arr:
    if p.get("slug") == "$slug":
        print(p.get("id"))
        sys.exit(0)
sys.exit(0)
PY
}

create_pack_if_missing() {
  slug="$1"
  title="$2"
  description="$3"
  game_type="$4"

  existing_id=$(get_pack_id "$slug")
  if [ -n "$existing_id" ]; then
    echo "Pack '$slug' already exists (id=$existing_id)."
    echo "$existing_id"
    return 0
  fi

  echo "Creating pack $slug ..."
  body=$(python3 - <<PY
import json,sys
slug=sys.argv[1]
title=sys.argv[2]
description=sys.argv[3]
game_type=sys.argv[4]
obj={'slug':slug,'title':title,'description':description,'game_type':game_type,'is_active':True}
print(json.dumps(obj))
PY
  "$slug" "$title" "$description" "$game_type")

  curl -s -X POST "${BASE}/api/admin/content-packs" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "$body" >/dev/null

  sleep 0.5
  get_pack_id "$slug"
}

create_version_for_pack() {
  pack_id="$1"
  pack_slug="$2"
  content_type="$3"

  published_at=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

  payload=$(python3 - <<PY
import json,sys
pack_slug=sys.argv[1]
content_type=sys.argv[2]
published_at=sys.argv[3]
payload={
  "meta": {"pack": pack_slug, "created_at": published_at},
  "contents": [
    {
      "type": content_type,
      "title": "Sample Item",
      "description": f"Sample item for {pack_slug}",
      "content": {"example": "sample"},
      "sequence_order": 1,
      "difficulty": "easy"
    }
  ],
  "schema_version": 2
}
print(json.dumps(payload))
PY
  "$pack_slug" "$content_type" "$published_at")

  echo "Creating published version for pack id $pack_id ..."
  body=$(python3 - <<PY
import json,sys
pack_id=int(sys.argv[1])
published_at=sys.argv[2]
payload=json.loads(sys.stdin.read())
obj={
  'content_pack_id': pack_id,
  'version': '1',
  'checksum': f'sha1-{pack_id}-v1',
  'size_bytes': 2048,
  'min_app_version': '1.0.0',
  'published_at': published_at,
  'payload': payload
}
print(json.dumps(obj))
PY
  "$pack_id" "$published_at" <<< "$payload")

  curl -s -X POST "${BASE}/api/admin/content-pack-versions" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "$body" >/dev/null

  echo "Done (pack $pack_id)."
}

read -r -d '' PACKS <<'EOM'
fidel-tracing-pack|Fidel Tracing Pack|Basic tracing activities for fidel characters|Fidel Tracing|fidel_tracing
voice-to-word-pack|Voice → Word Pack|Short voice prompts to map to words|Fidel Match|voice_to_word
picture-to-word-pack|Picture → Word Pack|Images mapped to vocabulary|Pic-to-Word|picture_to_word
word-builder-pack|Word Builder Pack|Activities to build words from letters|Word Builder|word_builder
fill-in-the-blank-pack|Fill In The Blank Pack|Cloze exercises and fill-in-the-blank tasks|Listen & Fill|fill_in_the_blank
pronunciation-pack|Pronunciation Pack|Pronunciation practice activities|Speak Up|pronunciation
story-quiz-pack|Story Quiz Pack|Short story comprehension quizzes|Story Quiz|story_quiz
EOM

echo "$PACKS" | while IFS='|' read -r slug title description game_type content_type; do
  echo "Processing $slug ..."
  id=$(create_pack_if_missing "$slug" "$title" "$description" "$game_type")
  if [ -z "$id" ]; then
    echo "Failed to determine id for $slug; trying again."
    id=$(get_pack_id "$slug")
  fi

  if [ -n "$id" ]; then
    create_version_for_pack "$id" "$slug" "$content_type"
  else
    echo "ERROR: could not get pack id for $slug; skipping."
  fi
done

echo "All done. Use the admin API to inspect created packs."
