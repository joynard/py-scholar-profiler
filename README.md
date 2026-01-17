# Py-PHP Scholar Profiler

Google Scholar Author Profile Crawler with Cosine Similarity Ranking
*(Python-PHP + SerpAPI)*

## Overview

Py Scholar Profiler is a Python-based system that crawls Google Scholar author profiles using SerpAPI and retrieves scientific publications along with their metadata.
In addition to data collection, the system computes textual similarity between a user query and article titles using **Cosine Similarity** to rank the most relevant papers.

This project is designed to assist researchers and students in finding relevant articles efficiently based on both author profiles and semantic similarity.

## Features

* Crawling Google Scholar author profiles via SerpAPI
* Extraction of publication metadata:

  * Title
  * Authors
  * Journal / Conference
  * Publication Year
  * Citation Count
  * Source Link
* Text vectorization using TF-IDF
* Relevance measurement using Cosine Similarity
* Ranking and tabular display of articles based on similarity scores

## Methodology

1. Query an author profile or keyword using SerpAPI.
2. Retrieve publication metadata from Google Scholar.
3. Preprocess and vectorize text using TF-IDF.
4. Compute Cosine Similarity between the user query and article titles.
5. Rank the results based on similarity scores.
6. Display the ranked list in a structured table.

## Example Output

The system produces a table containing:

* Article Title
* Authors
* Venue (Journal / Conference)
* Year
* Citation Count
* Link
* Cosine Similarity Score
<img width="1913" height="1327" alt="image" src="https://github.com/user-attachments/assets/e791f60b-d290-496f-ae9b-16e8ca91c5c1" />

Enter the author profile or query when prompted. The system will retrieve publications and rank them using cosine similarity.

## License

This project is licensed under the MIT License.
