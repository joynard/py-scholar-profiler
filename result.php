<?php
require_once __DIR__ . '/vendor/autoload.php';
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
$pythonPath = "py";


$authorName = $_POST['author_name'] ?? '';
$keyword = $_POST['keyword'] ?? '';
$limit = (int) ($_POST['limit'] ?? 5);


if ($authorName === '' || $keyword === '') {
    die("Input tidak lengkap");
}

$command = shell_exec($pythonPath . ' "' . __DIR__ . '/scholars_author.py" ' . escapeshellarg($authorName) . " 2>&1");

// Read and decode the JSON file
$json_file = 'results_author.json';
if (!file_exists($json_file)) {
    echo "<h1>Error</h1><p>Results file not found.</p>";
    exit;
}

$json_content = file_get_contents($json_file);
$data = json_decode($json_content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<h1>Error</h1><p>Failed to decode JSON: " . json_last_error_msg() . "</p><pre>$json_content</pre>";
    exit;
}

$author_id = $data['profiles']['authors'][0]['author_id'];

$command = shell_exec($pythonPath . ' "' . __DIR__ . '/scholars_title.py" ' . escapeshellarg($author_id) . " 2>&1");


// Read and decode the JSON file
$json_file = 'results_title.json';
if (!file_exists($json_file)) {
    echo "<h1>Error</h1><p>Results file not found.</p>";
    exit;
}

$json_content = file_get_contents($json_file);
$data = json_decode($json_content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<h1>Error</h1><p>Failed to decode JSON: " . json_last_error_msg() . "</p><pre>$json_content</pre>";
    exit;
}

if (isset($data['articles']) && is_array($data['articles'])) {
    $topArticles = array_slice($data['articles'], 0, $limit);

    $articlesData = [];
    $similarityData = [];

    foreach ($topArticles as $article) {
        $newsTitle   = $article['title'] ?? '-';
        $journalLink = $article['link'] ?? '#'; 
        
        // Di JSON SerpApi, sitasi ada di dalam object 'cited_by'
        $sitasi      = $article['cited_by']['value'] ?? 0;
        
        // Di JSON SerpApi, penulis & jurnal biasanya sudah ada di key ini
        $authors     = $article['authors'] ?? '-'; 
        $journal     = $article['publication'] ?? '-';
        $rilis       = $article['year'] ?? '-';

        // Masukkan ke array data
        $articlesData[] = [
            'title' => $newsTitle,
            'authors' => $authors,
            'journal' => $journal,
            'date' => $rilis,
            'citations' => $sitasi,
            'journal_link' => $journalLink,
            'similarity' => 0 
        ];
        
        $command = shell_exec($pythonPath . ' "' . __DIR__ . '/translate.py" ' . escapeshellarg($newsTitle) . " 2>&1");
        
        // Bersihkan hasil translate
        $cleanText = strtolower(trim($command));
        
        // Fallback: Jika translate gagal/kosong, pakai judul asli saja
        if (empty($cleanText)) {
            $cleanText = strtolower($newsTitle);
        }

        $similarityData[] = $cleanText;
    }

    $command = shell_exec($pythonPath . ' "' . __DIR__ . '/translate.py" ' . escapeshellarg($keyword) . " 2>&1");
    $translated = trim($command);
    $similarityData[] = strtolower($translated);
    file_put_contents(
        'similarity_data.json',
        json_encode($similarityData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    /*
    // Perhitungan Similarity dengan PHP-ML
    // --- AREA DEBUGGING ---
    echo "<h3>Debug Data Translate:</h3>";
    echo "<pre>";
    print_r($similarityData); // Cek apakah isinya teks judul indo atau kosong?
    echo "</pre>";

    if (empty($similarityData[0])) {
        die("<b>FATAL ERROR:</b> Hasil translate kosong! Script Python tidak mengembalikan output ke PHP.");
    }
    // --- END DEBUGGING ---
    */
    
    if (count($similarityData) > 1) {
        // Dokumen terakhir adalah KEYWORD, sisanya adalah JUDUL ARTIKEL
        $documents = $similarityData;
        
        // 2. Tokenisasi & Vektorisasi (Mengubah teks jadi angka)
        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $vectorizer->fit($documents);
        $vectorizer->transform($documents);

        // 3. TF-IDF (Memberi bobot pada kata penting)
        $transformer = new TfIdfTransformer();
        $transformer->fit($documents);
        $transformer->transform($documents);

        // 4. Ambil Vektor Keyword (elemen terakhir)
        $keywordVector = end($documents);
        array_pop($documents); // Buang keyword dari list agar sisa artikel saja

        // Fungsi Cosine Similarity Manual (sederhana & efektif)
        function hitungCosine($vecA, $vecB) {
            $dot = 0;
            $normA = 0; 
            $normB = 0;
            
            // Dot Product
            foreach ($vecA as $key => $val) {
                if (isset($vecB[$key])) {
                    $dot += $val * $vecB[$key];
                }
            }
            // Magnitude A
            foreach ($vecA as $val) $normA += $val * $val;
            // Magnitude B
            foreach ($vecB as $val) $normB += $val * $val;

            if ($normA == 0 || $normB == 0) return 0;
            return $dot / (sqrt($normA) * sqrt($normB));
        }

        // 5. Masukkan nilai similarity ke array data artikel
        foreach ($articlesData as $idx => &$article) {
            if (isset($documents[$idx])) {
                $score = hitungCosine($documents[$idx], $keywordVector);
                $article['similarity'] = $score;
            }
        }
        unset($article); // Hapus referensi
    }

    $similarities = array_column($articlesData, 'similarity');
    array_multisort($similarities, SORT_DESC, SORT_NUMERIC, $articlesData);

    echo "
<style>
    body { font-family: Arial, sans-serif; background: #f5f7fa; }
    .container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
    h2 { text-align: center; margin-bottom: 20px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 12px; font-size: 14px; text-align: center; border-bottom: 1px solid #ddd; }
    th { background: #4f46e5; color: #fff; }
    td { vertical-align: middle; }
    tr:hover { background: #f1f5f9; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 13px; color: #fff; background: #22c55e; display: inline-block; }
    .btn, .btn-back { display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.3s; }
    .btn { background: #2563eb; color: #fff; }
    .btn:hover { background: #1e40af; }
    .btn-back { padding: 10px 20px; background: #64748b; color: #fff; margin-bottom: 20px; border-radius: 8px; font-size: 14px; }
    .btn-back:hover { background: #475569; }
</style>

<div class='container'>
    <h2>🔍 Search Results</h2>
    <table>
        <tr>
            <th>Judul Artikel</th>
            <th>Penulis</th>
            <th>Jurnal</th>
            <th>Tahun</th>
            <th>Sitasi</th>
            <th>Link</th>
            <th>Similarity</th>
        </tr>
";

    foreach ($articlesData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['authors']) . "</td>";
        echo "<td>" . htmlspecialchars($row['journal']) . "</td>";
        echo "<td class='center'>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td class='center'>" . (int) $row['citations'] . "</td>";

        // Link jurnal
        if ($row['journal_link'] !== '-') {
            echo 
            "<td class='center'>
                <a class='btn' href='" . htmlspecialchars($row['journal_link']) . "' target='_blank'>
                    Buka
                </a>
            </td>";
        } else {
            echo "<td class='center'>-</td>";
        }

        // Similarity
        echo "<td class='center'>
            <span class='badge'>" . number_format((float) $row['similarity'], 4) . "</span>
            </td>";
        echo "</tr>";
    }

    echo "
    </table>

    <div style='margin-top: 20px; text-align: center;'>
            <a href='index.php' class='btn-back'>Kembali ke Pencarian</a>
    </div>
</div>
";
}
?>