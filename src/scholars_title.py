import json
import sys
from serpapi import GoogleSearch
from config import SERPAPI_API

api_key = SERPAPI_API

try:
    # 1. Tangkap Input dari Argumen
    author_id = sys.argv[1]
    
    # Tangkap limit, default 10 jika tidak dikirim
    try:
        limit = int(sys.argv[2])
    except IndexError:
        limit = 10

    # 2. Request Pertama: Ambil Daftar Artikel
    params = {
        "api_key": api_key,
        "engine": "google_scholar_author",
        "hl": "en",
        "author_id": author_id,
        "num": limit,
    }

    search = GoogleSearch(params)
    results = search.get_dict()

    # 3. Request Kedua: Loop Ambil Detail Artikel
    if "articles" in results:
        # Potong list sesuai limit
        top_articles = results["articles"][:limit]
        detailed_articles = []

        for article in top_articles:
            # Kita butuh citation_id untuk melihat detail
            if "citation_id" in article:
                params_detail = {
                    "api_key": api_key,
                    "engine": "google_scholar_author",
                    "view_op": "view_citation",
                    "citation_id": article["citation_id"],
                    "hl": "en"
                }

                try:
                    # Request detail ke SerpApi
                    search_detail = GoogleSearch(params_detail)
                    res_detail = search_detail.get_dict()

                    if "citation" in res_detail:
                        citation_data = res_detail["citation"]
                        
                        # A. Update Penulis
                        if "authors" in citation_data:
                            article["authors"] = citation_data["authors"]

                        # B. Update Jurnal
                        if "journal" in citation_data:
                            article["publication"] = citation_data["journal"]

                        # C. Update Tahun
                        if "pub_date" in citation_data:
                            article["year"] = citation_data["pub_date"]

                except Exception:
                    pass
            
            detailed_articles.append(article)
        
        # Ganti data artikel lama dengan yang sudah didetailkan
        results["articles"] = detailed_articles

    # 4. Simpan Hasil ke JSON
    with open('results_title.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=4)

except Exception as e:
    # Simpan pesan error ke JSON agar PHP bisa membacanya
    error_response = {"error": str(e)}
    with open('results_title.json', 'w', encoding='utf-8') as f:
        json.dump(error_response, f, ensure_ascii=False, indent=4)