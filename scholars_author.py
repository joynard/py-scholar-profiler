import json
import sys
from serpapi import GoogleSearch

penulis = sys.argv[1]
# print(penulis)
params = {
  "engine": "google_scholar",
  "q": penulis,
  "hl": "en",
  "api_key": "90a0bdcb0dc14fe26c23bf7fe14974eacc21230f4c40eb9f59523bf712d468dd"
}

search = GoogleSearch(params)
results = search.get_dict()

with open('results_author.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, ensure_ascii=False, indent=4)