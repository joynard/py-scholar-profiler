import json
import sys
from serpapi import GoogleSearch
from config import SERPAPI_API

author_id = sys.argv[1]

params = {
  "api_key": SERPAPI_API,
  "engine": "google_scholar_author",
  "hl": "en",
  "author_id": author_id
}

search = GoogleSearch(params)
results = search.get_dict()

# Save the full results
with open('results_title.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, ensure_ascii=False, indent=4)
