import json
import sys
from serpapi import GoogleSearch

author_id = sys.argv[1]

params = {
  "api_key": "90a0bdcb0dc14fe26c23bf7fe14974eacc21230f4c40eb9f59523bf712d468dd",
  "engine": "google_scholar_author",
  "hl": "en",
  "author_id": author_id
}

search = GoogleSearch(params)
results = search.get_dict()

# Save the full results
with open('results_title.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, ensure_ascii=False, indent=4)
