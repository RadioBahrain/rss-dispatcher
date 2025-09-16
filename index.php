<?php

/**
 * RSS Dispatcher Web Interface
 * 
 * Web-based interface for playlist to RSS conversion
 * 
 * @author Radio Bahrain Development Team
 * @version 1.0.0
 * @license MIT
 */

require_once 'func.php';
require_once 'playlist-to-rss.php';

/*----------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 * Licensed under the MIT License. See LICENSE in the project root for license information.
 *---------------------------------------------------------------------------------------*/

// Handle conversion requests
if (isset($_GET['convert']) && isset($_GET['output'])) {
    $inputFile = $_GET['convert'];
    $outputFile = $_GET['output'];
    
    try {
        $converter = new PlaylistToRSSConverter([
            'title' => 'Radio Bahrain RSS Feed',
            'description' => 'Generated RSS feed from ' . basename($inputFile),
            'link' => 'https://radiobahrain.fm'
        ]);
        
        $converter->convertPlaylistToRSS($inputFile, $outputFile);
        $message = "Successfully converted " . basename($inputFile) . " to RSS feed!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>هنا البحرين:: مولد RSS</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.2em;
            margin: 10px 0 0 0;
        }
        
        .rss-interface {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        
        .rss-interface h2 {
            color: #495057;
            margin-top: 0;
            font-size: 1.8em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .rss-interface h3 {
            color: #6c757d;
            margin-top: 25px;
        }
        
        .rss-interface ul {
            list-style: none;
            padding: 0;
        }
        
        .rss-interface li {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 0 5px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-convert {
            background: #28a745;
            color: white;
        }
        
        .btn-convert:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-download {
            background: #007bff;
            color: white;
        }
        
        .btn-download:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }
        
        .feature-card h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        
        .feature-card p {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            margin: 20px 0;
        }
        
        .upload-area:hover {
            background: #e9ecef;
            border-color: #5a67d8;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .rss-interface li {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn {
                margin: 5px 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🎵 مولد RSS للبحرين</h1>
            <p>Radio Bahrain RSS Feed Generator</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="features">
            <div class="feature-card">
                <h3>🎼 تحويل قوائم التشغيل</h3>
                <p>يدعم تنسيقات M3U و PLS و TXT لتحويلها إلى RSS feeds قياسية مع metadata كاملة</p>
            </div>
            
            <div class="feature-card">
                <h3>⚡ معالجة سريعة</h3>
                <p>تحويل فوري للملفات مع إنتاج RSS feeds متوافقة مع جميع البودكاست والتطبيقات</p>
            </div>
            
            <div class="feature-card">
                <h3>🔧 واجهة سهلة</h3>
                <p>واجهة ويب بسيطة وأدوات سطر أوامر للاستخدام المتقدم والأتمتة</p>
            </div>
        </div>

        <?php echo render_rss_interface(); ?>

        <div class="upload-area">
            <h3>📁 رفع ملفات جديدة</h3>
            <p>قم بإضافة ملفات M3U أو PLS أو TXT إلى مجلد المشروع ثم قم بتحديث الصفحة</p>
            <p>أو استخدم أداة سطر الأوامر: <code>php playlist-to-rss.php input.m3u output.rss</code></p>
        </div>

        <div class="rss-interface">
            <h2>معلومات تقنية</h2>
            <p><strong>إصدار PHP:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>ملفات مدعومة:</strong> M3U, M3U8, PLS, TXT</p>
            <p><strong>تنسيق الإخراج:</strong> RSS 2.0 XML مع دعم iTunes</p>
            <p><strong>الترميز:</strong> UTF-8</p>
            
            <h3>أمثلة للاستخدام:</h3>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto;">
# تحويل ملف واحد
php playlist-to-rss.php playlist.m3u feed.rss

# معالجة متعددة
php playlist-to-rss.php --batch playlists/ feeds/

# عرض المساعدة
php playlist-to-rss.php --help
            </pre>
        </div>

        <div class="footer">
            <p>&copy; 2024 Radio Bahrain - RSS Dispatcher v1.0.0</p>
            <p>Powered by PHP <?php echo PHP_VERSION; ?> | Licensed under MIT</p>
        </div>
    </div>
</body>
</html>