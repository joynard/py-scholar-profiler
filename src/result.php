<?php
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'Sastrawi\\';
    $base_dir = __DIR__ . '/../vendor/sastrawi/sastrawi/src/Sastrawi/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;

use Sastrawi\StopWordRemover\StopWordRemoverFactory;
use Sastrawi\Stemmer\StemmerFactory;

// --- INISIALISASI SASTRAWI ---
$stopWordFactory = new StopWordRemoverFactory();
$stopword = $stopWordFactory->createStopWordRemover();

$stemmerFactory = new StemmerFactory();
$stemmer = $stemmerFactory->createStemmer();

$pythonPath = "py"; 

$authorName = $_POST['author_name'] ?? '';
$keyword = $_POST['keyword'] ?? '';
$limit = (int) ($_POST['limit'] ?? 5);

if ($authorName === '' || $keyword === '') {
    die("Input tidak lengkap");
}

// 1. Cari Author ID
$command = shell_exec($pythonPath . ' "' . __DIR__ . '/scholars_author.py" ' . escapeshellarg($authorName) . " 2>&1");

$json_file = 'results_author.json';
if (!file_exists($json_file)) {
    echo "<h1>Error</h1><p>Results file not found.</p>";
    exit;
}

$dataAuthor = json_decode(file_get_contents($json_file), true);
$author_id = $dataAuthor['profiles']['authors'][0]['author_id'] ?? null;

if (!$author_id) {
    die("Author ID tidak ditemukan. Coba nama lain.");
}

// 2. Cari Artikel (Detail)
$command = shell_exec($pythonPath . ' "' . __DIR__ . '/scholars_title.py" ' . escapeshellarg($author_id) . ' ' . escapeshellarg($limit) . " 2>&1");

$json_file = 'results_title.json';
if (!file_exists($json_file)) {
    echo "<h1>Error</h1><p>Results file not found.</p>";
    exit;
}

$data = json_decode(file_get_contents($json_file), true);

if (isset($data['articles']) && is_array($data['articles'])) {
    $topArticles = array_slice($data['articles'], 0, $limit);

    $articlesData = [];
    $similarityData = [];

    foreach ($topArticles as $article) {
        $newsTitle   = $article['title'] ?? '-';
        $journalLink = $article['link'] ?? '#'; 
        $sitasi      = $article['cited_by']['value'] ?? 0;
        $authors     = $article['authors'] ?? '-'; 
        $journal     = $article['publication'] ?? '-';
        $rilis       = $article['year'] ?? '-';

        // Simpan data asli untuk ditampilkan di tabel
        $articlesData[] = [
            'title' => $newsTitle,
            'authors' => $authors,
            'journal' => $journal,
            'date' => $rilis,
            'citations' => $sitasi,
            'journal_link' => $journalLink,
            'similarity' => 0 
        ];
        
        // --- PROSES PREPROCESSING ---
        
        // 1. Translate (Inggris -> Indonesia)
        $command = shell_exec($pythonPath . ' "' . __DIR__ . '/translate.py" ' . escapeshellarg($newsTitle) . " 2>&1");
        $text = strtolower(trim($command));
        if (empty($text)) $text = strtolower($newsTitle);

        // 2. Sastrawi: Hapus Stopword (dan, yang, di, dll)
        $text = $stopword->remove($text);

        // 3. Sastrawi: Stemming
        $text = $stemmer->stem($text);

        $similarityData[] = $text;
    }

    // --- PROSES KEYWORD USER ---
    $command = shell_exec($pythonPath . ' "' . __DIR__ . '/translate.py" ' . escapeshellarg($keyword) . " 2>&1");
    $keyText = strtolower(trim($command));
    if (empty($keyText)) $keyText = strtolower($keyword);

    // Terapkan Sastrawi juga pada keyword
    $keyText = $stopword->remove($keyText);
    $keyText = $stemmer->stem($keyText);

    $similarityData[] = $keyText;

    // Simpan data similarity untuk debugging (opsional)
    file_put_contents(
        'similarity_data.json',
        json_encode($similarityData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    
    // --- HITUNG SIMILARITY (TF-IDF & COSINE) ---
    if (count($similarityData) > 1) {
        $documents = $similarityData;
        
        // Tokenisasi
        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $vectorizer->fit($documents);
        $vectorizer->transform($documents);

        // TF-IDF
        $transformer = new TfIdfTransformer();
        $transformer->fit($documents);
        $transformer->transform($documents);

        // Ambil Vektor Keyword (elemen terakhir)
        $keywordVector = end($documents);
        array_pop($documents); 

        // Fungsi Cosine Similarity
        function hitungCosine($vecA, $vecB) {
            $dot = 0; $normA = 0; $normB = 0;
            foreach ($vecA as $key => $val) {
                if (isset($vecB[$key])) {
                    $dot += $val * $vecB[$key];
                }
            }
            foreach ($vecA as $val) $normA += $val * $val;
            foreach ($vecB as $val) $normB += $val * $val;

            return ($normA == 0 || $normB == 0) ? 0 : $dot / (sqrt($normA) * sqrt($normB));
        }

        // Update nilai similarity
        foreach ($articlesData as $idx => &$article) {
            if (isset($documents[$idx])) {
                $score = hitungCosine($documents[$idx], $keywordVector);
                $article['similarity'] = $score;
            }
        }
        unset($article);
    }

    // Sorting dari terbesar ke terkecil
    $similarities = array_column($articlesData, 'similarity');
    array_multisort($similarities, SORT_DESC, SORT_NUMERIC, $articlesData);

    // --- TAMPILAN HTML ---
    echo "
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h2 { text-align: center; margin-bottom: 25px; color: #1e293b; font-weight: 700; }
        
        /* Tombol Kembali (Di Bawah) */
        .btn-back {
            display: inline-flex; align-items: center; padding: 12px 25px;
            background-color: #64748b; color: white; text-decoration: none;
            border-radius: 8px; font-size: 14px; font-weight: 600;
            transition: background 0.3s; margin-top: 20px;
        }
        .btn-back:hover { background-color: #475569; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        th { background: #2563eb; color: #fff; padding: 15px; font-size: 14px; text-align: left; font-weight: 600; }
        td { padding: 12px 15px; font-size: 14px; border-bottom: 1px solid #e2e8f0; color: #334155; vertical-align: middle; }
        .center { text-align: center; }
        tr:hover { background: #f8fafc; }
        
        .badge { padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: bold; color: #fff; background: #10b981; }
        .btn-link { padding: 6px 14px; background: #3b82f6; color: #fff; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; }
        .btn-link:hover { background: #2563eb; }
    </style>

    <div class='container'>
        <h2>Hasil Pencarian</h2>
        
        <table>
            <tr>
                <th>Judul Artikel</th>
                <th>Penulis</th>
                <th>Jurnal</th>
                <th class='center'>Tahun</th>
                <th class='center'>Sitasi</th>
                <th class='center'>Link</th>
                <th class='center'>Similarity</th>
            </tr>
    ";

    foreach ($articlesData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['authors']) . "</td>";
        echo "<td>" . htmlspecialchars($row['journal']) . "</td>";
        echo "<td class='center'>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td class='center'>" . (int) $row['citations'] . "</td>";

        if ($row['journal_link'] !== '-' && $row['journal_link'] !== '#') {
            echo "<td class='center'><a class='btn-link' href='" . htmlspecialchars($row['journal_link']) . "' target='_blank'>Buka</a></td>";
        } else {
            echo "<td class='center'>-</td>";
        }

        echo "<td class='center'><span class='badge'>" . number_format((float) $row['similarity'], 4) . "</span></td>";
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