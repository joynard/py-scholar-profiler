<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pencarian Data Artikel Ilmiah</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; background: linear-gradient(120deg, #e0f2ff, #f8fbff); min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; }
        .container { background: #ffffff; width: 420px; padding: 30px 35px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .container h2 { text-align: center; margin-bottom: 25px; color: #1e3a8a; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.3s; }
        input:focus { border-color: #2563eb; }
        .btn { width: 100%; padding: 12px; background: #2563eb; border: none; border-radius: 8px; color: #ffffff; font-size: 15px;font-weight: 600;cursor: pointer;transition: 0.3s;}
        .btn:hover { background: #1e40af;}
        .footer { text-align: center; margin-top: 15px; font-size: 12px; color: #6b7280;}
    </style>
</head>
<body>

<div class="container">
    <h2>PENCARIAN DATA<br>ARTIKEL ILMIAH</h2>

    <form action="result.php" method="POST">
        <div class="form-group">
            <label>Nama Penulis</label>
            <input type="text" name="author_name" placeholder="Contoh: Joko Widodo" required>
        </div>

        <div class="form-group">
            <label>Keyword Artikel</label>
            <input type="text" name="keyword" placeholder="Contoh: Machine Learning" required>
        </div>

        <div class="form-group">
            <label>Jumlah Data</label>
            <input type="number" name="limit" value="10" min="1" max="50" required>
        </div>

        <button type="submit" class="btn">🔍 Cari Artikel</button>
    </form>

    <div class="footer">
        Sistem Pencarian Artikel Ilmiah<br>
        Berbasis Google Scholar & Python
    </div>
</div>

</body>
</html>
