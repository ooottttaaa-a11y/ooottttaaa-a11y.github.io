$baseDir = "c:\xampp\htdocs\ohara\ooottttaaa-a11y.github.io\ohara"
$fileListPath = Join-Path $baseDir "A_files_list.txt"
$outputHtmlPath = Join-Path $baseDir "merged_all.html"

# ファイルリストの読み込み
$files = Get-Content $fileListPath -Encoding UTF8

# HTMLヘッダーの準備（最初のファイルからスタイルなどを取得するが、干渉を避けるため最小限にするか検討）
# ここでは、共通のスタイルを持つ前提で、最初のファイルからヘッダーをコピーし、
# bodyの閉じタグ直前までを使用する。
$firstFile = $files[0]
$firstContent = Get-Content $firstFile -Raw -Encoding UTF8

# ヘッダー部分の抽出（<body ...> まで）
if ($firstContent -match '(?s)(.*?)<body.*?>') {
    $header = $matches[1] + "<body>"
} else {
    Write-Error "Could not find body tag in the first file."
    exit 1
}

# 追加のスタイル（改ページ用）
$pageBreakStyle = @"
<style>
    @media print {
        .page-break { page-break-before: always; }
        /* 印刷時に余白などを調整する場合 */
        @page { margin: 10mm; }
    }
    .page-break {
        border-top: 1px dashed #ccc;
        margin-top: 20px;
        padding-top: 20px;
    }
    /* 結合時のID重複などが問題になる場合はここでスタイル調整が必要かもしれません */
</style>
"@

$header = $header.Replace("</head>", "$pageBreakStyle`n</head>")

# 出力ファイルの初期化
Set-Content -Path $outputHtmlPath -Value $header -Encoding UTF8

# 各ファイルの処理
foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Processing: $file"
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # body内のコンテンツを抽出
        if ($content -match '(?s)<body.*?>(.*?)</body>') {
            $bodyContent = $matches[1]
            
            # ページ区切りdivで囲んで追加
            $chunk = "<div class='page-break'>`n$bodyContent`n</div>"
            Add-Content -Path $outputHtmlPath -Value $chunk -Encoding UTF8
        }
    } else {
        Write-Warning "File not found: $file"
    }
}

# 終了タグの追加
Add-Content -Path $outputHtmlPath -Value "</body></html>" -Encoding UTF8

Write-Host "Merged HTML created at: $outputHtmlPath"
