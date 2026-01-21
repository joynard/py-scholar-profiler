import json
import sys
from serpapi import GoogleSearch
from config import SERPAPI_API

penulis = sys.argv[1]
params = {
  "engine": "google_scholar",
  "q": penulis,
  "hl": "en",
  "api_key": SERPAPI_API
}

search = GoogleSearch(params)
results = search.get_dict()

with open('results_author.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, ensure_ascii=False, indent=4)