<?php

/**
 * RSS Dispatcher - Playlist to RSS Converter
 * 
 * A comprehensive utility for converting various playlist formats to RSS feeds.
 * Supports M3U, PLS, and custom playlist formats with extensible architecture.
 * 
 * @author Radio Bahrain Development Team
 * @version 1.0.0
 * @license MIT
 */

require_once 'func.php';

class PlaylistToRSSConverter
{
    private $options;
    private $supportedFormats = ['m3u', 'm3u8', 'pls', 'txt'];
    
    public function __construct($options = [])
    {
        $this->options = array_merge([
            'title' => 'Radio Bahrain Playlist',
            'description' => 'Generated RSS feed from playlist',
            'link' => 'https://radiobahrain.fm',
            'language' => 'ar-BH',
            'generator' => 'RSS Dispatcher v1.0.0',
            'webmaster' => 'info@radiobahrain.fm',
            'managingEditor' => 'editor@radiobahrain.fm',
            'ttl' => 60,
            'image_url' => '',
            'image_title' => 'Radio Bahrain',
            'image_link' => 'https://radiobahrain.fm'
        ], $options);
    }
    
    /**
     * Convert a playlist file to RSS format
     * 
     * @param string $inputFile Path to the input playlist file
     * @param string $outputFile Path for the output RSS file (optional)
     * @return string|bool RSS content on success, false on failure
     */
    public function convertPlaylistToRSS($inputFile, $outputFile = null)
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file not found: {$inputFile}");
        }
        
        $extension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->supportedFormats)) {
            throw new Exception("Unsupported playlist format: {$extension}");
        }
        
        $playlistData = $this->parsePlaylist($inputFile, $extension);
        $rssContent = $this->generateRSS($playlistData);
        
        if ($outputFile) {
            $this->saveRSSToFile($rssContent, $outputFile);
        }
        
        return $rssContent;
    }
    
    /**
     * Parse playlist file based on format
     * 
     * @param string $file Path to playlist file
     * @param string $format File format (m3u, pls, etc.)
     * @return array Parsed playlist data
     */
    private function parsePlaylist($file, $format)
    {
        $content = file_get_contents($file);
        $playlistData = [];
        
        switch ($format) {
            case 'm3u':
            case 'm3u8':
                $playlistData = $this->parseM3U($content);
                break;
            case 'pls':
                $playlistData = $this->parsePLS($content);
                break;
            case 'txt':
                $playlistData = $this->parseTextPlaylist($content);
                break;
            default:
                throw new Exception("Parser not implemented for format: {$format}");
        }
        
        return $playlistData;
    }
    
    /**
     * Parse M3U playlist format
     * 
     * @param string $content File content
     * @return array Parsed entries
     */
    private function parseM3U($content)
    {
        $lines = explode("\n", $content);
        $entries = [];
        $currentEntry = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line[0] === '#') {
                if (strpos($line, '#EXTINF:') === 0) {
                    // Extract metadata from EXTINF line
                    preg_match('/#EXTINF:(-?\d+),(.*)/', $line, $matches);
                    if ($matches) {
                        $currentEntry['duration'] = intval($matches[1]);
                        $currentEntry['title'] = trim($matches[2]);
                    }
                }
                continue;
            }
            
            // This line should be a URL
            $currentEntry['url'] = $line;
            $currentEntry['title'] = $currentEntry['title'] ?? basename($line);
            $currentEntry['description'] = "Audio track: " . $currentEntry['title'];
            $currentEntry['pubDate'] = date('r');
            $currentEntry['guid'] = md5($line . time());
            
            $entries[] = $currentEntry;
            $currentEntry = [];
        }
        
        return $entries;
    }
    
    /**
     * Parse PLS playlist format
     * 
     * @param string $content File content
     * @return array Parsed entries
     */
    private function parsePLS($content)
    {
        $lines = explode("\n", $content);
        $entries = [];
        $tracks = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^File(\d+)=(.*)/', $line, $matches)) {
                $trackNum = intval($matches[1]);
                $tracks[$trackNum]['url'] = $matches[2];
            } elseif (preg_match('/^Title(\d+)=(.*)/', $line, $matches)) {
                $trackNum = intval($matches[1]);
                $tracks[$trackNum]['title'] = $matches[2];
            } elseif (preg_match('/^Length(\d+)=(.*)/', $line, $matches)) {
                $trackNum = intval($matches[1]);
                $tracks[$trackNum]['duration'] = intval($matches[2]);
            }
        }
        
        foreach ($tracks as $track) {
            if (isset($track['url'])) {
                $entries[] = [
                    'title' => $track['title'] ?? basename($track['url']),
                    'url' => $track['url'],
                    'description' => "Audio track: " . ($track['title'] ?? basename($track['url'])),
                    'duration' => $track['duration'] ?? -1,
                    'pubDate' => date('r'),
                    'guid' => md5($track['url'] . time())
                ];
            }
        }
        
        return $entries;
    }
    
    /**
     * Parse simple text playlist (one URL per line)
     * 
     * @param string $content File content
     * @return array Parsed entries
     */
    private function parseTextPlaylist($content)
    {
        $lines = explode("\n", $content);
        $entries = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            $entries[] = [
                'title' => basename($line),
                'url' => $line,
                'description' => "Audio track: " . basename($line),
                'pubDate' => date('r'),
                'guid' => md5($line . time())
            ];
        }
        
        return $entries;
    }
    
    /**
     * Generate RSS XML from playlist data
     * 
     * @param array $playlistData Parsed playlist entries
     * @return string RSS XML content
     */
    private function generateRSS($playlistData)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Create RSS root element
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $xml->appendChild($rss);
        
        // Create channel
        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);
        
        // Add channel metadata
        $this->addChannelMetadata($xml, $channel);
        
        // Add items
        foreach ($playlistData as $entry) {
            $this->addRSSItem($xml, $channel, $entry);
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Add channel metadata to RSS
     * 
     * @param DOMDocument $xml XML document
     * @param DOMElement $channel Channel element
     */
    private function addChannelMetadata($xml, $channel)
    {
        $metadata = [
            'title' => $this->options['title'],
            'description' => $this->options['description'],
            'link' => $this->options['link'],
            'language' => $this->options['language'],
            'generator' => $this->options['generator'],
            'webMaster' => $this->options['webmaster'],
            'managingEditor' => $this->options['managingEditor'],
            'ttl' => $this->options['ttl'],
            'lastBuildDate' => date('r'),
            'pubDate' => date('r')
        ];
        
        foreach ($metadata as $tag => $value) {
            $element = $xml->createElement($tag, htmlspecialchars($value));
            $channel->appendChild($element);
        }
        
        // Add image if provided
        if (!empty($this->options['image_url'])) {
            $image = $xml->createElement('image');
            $image->appendChild($xml->createElement('url', htmlspecialchars($this->options['image_url'])));
            $image->appendChild($xml->createElement('title', htmlspecialchars($this->options['image_title'])));
            $image->appendChild($xml->createElement('link', htmlspecialchars($this->options['image_link'])));
            $channel->appendChild($image);
        }
    }
    
    /**
     * Add an RSS item to the channel
     * 
     * @param DOMDocument $xml XML document
     * @param DOMElement $channel Channel element
     * @param array $entry Entry data
     */
    private function addRSSItem($xml, $channel, $entry)
    {
        $item = $xml->createElement('item');
        
        $item->appendChild($xml->createElement('title', htmlspecialchars($entry['title'])));
        $item->appendChild($xml->createElement('description', htmlspecialchars($entry['description'])));
        $item->appendChild($xml->createElement('link', htmlspecialchars($entry['url'])));
        $item->appendChild($xml->createElement('guid', htmlspecialchars($entry['guid'])));
        $item->appendChild($xml->createElement('pubDate', $entry['pubDate']));
        
        // Add enclosure for audio files
        $enclosure = $xml->createElement('enclosure');
        $enclosure->setAttribute('url', htmlspecialchars($entry['url']));
        $enclosure->setAttribute('type', $this->getMimeType($entry['url']));
        $enclosure->setAttribute('length', '0'); // Could be calculated if file is accessible
        $item->appendChild($enclosure);
        
        // Add iTunes-specific tags
        if (isset($entry['duration']) && $entry['duration'] > 0) {
            $duration = $xml->createElement('itunes:duration', $this->formatDuration($entry['duration']));
            $item->appendChild($duration);
        }
        
        $channel->appendChild($item);
    }
    
    /**
     * Get MIME type for audio file
     * 
     * @param string $url File URL
     * @return string MIME type
     */
    private function getMimeType($url)
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac'
        ];
        
        return $mimeTypes[$extension] ?? 'audio/mpeg';
    }
    
    /**
     * Format duration in iTunes format (HH:MM:SS)
     * 
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function formatDuration($seconds)
    {
        if ($seconds <= 0) {
            return '00:00:00';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    
    /**
     * Save RSS content to file
     * 
     * @param string $rssContent RSS XML content
     * @param string $outputFile Output file path
     * @return bool Success status
     */
    private function saveRSSToFile($rssContent, $outputFile)
    {
        $directory = dirname($outputFile);
        
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Cannot create directory: {$directory}");
            }
        }
        
        if (file_put_contents($outputFile, $rssContent) === false) {
            throw new Exception("Cannot write to file: {$outputFile}");
        }
        
        return true;
    }
    
    /**
     * Validate RSS feed
     * 
     * @param string $rssContent RSS XML content
     * @return bool Validation result
     */
    public function validateRSS($rssContent)
    {
        $xml = new DOMDocument();
        $xml->loadXML($rssContent);
        
        // Basic structure validation
        $rssElements = $xml->getElementsByTagName('rss');
        if ($rssElements->length !== 1) {
            return false;
        }
        
        $channelElements = $xml->getElementsByTagName('channel');
        if ($channelElements->length !== 1) {
            return false;
        }
        
        $requiredChannelElements = ['title', 'description', 'link'];
        $channel = $channelElements->item(0);
        
        foreach ($requiredChannelElements as $required) {
            $elements = $channel->getElementsByTagName($required);
            if ($elements->length === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Process multiple playlists in batch
     * 
     * @param string $inputDir Input directory containing playlists
     * @param string $outputDir Output directory for RSS files
     * @return array Processing results
     */
    public function batchConvert($inputDir, $outputDir)
    {
        if (!is_dir($inputDir)) {
            throw new Exception("Input directory not found: {$inputDir}");
        }
        
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new Exception("Cannot create output directory: {$outputDir}");
            }
        }
        
        $results = [];
        $files = glob($inputDir . '/*.{' . implode(',', $this->supportedFormats) . '}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $basename = pathinfo($file, PATHINFO_FILENAME);
            $outputFile = $outputDir . '/' . $basename . '.rss';
            
            try {
                $this->convertPlaylistToRSS($file, $outputFile);
                $results[$file] = ['status' => 'success', 'output' => $outputFile];
            } catch (Exception $e) {
                $results[$file] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
        
        return $results;
    }
}

/**
 * Command-line interface
 */
function main($argc, $argv)
{
    if ($argc < 2) {
        echo "RSS Dispatcher - Playlist to RSS Converter\n\n";
        echo "Usage:\n";
        echo "  php playlist-to-rss.php <input_file> [output_file]\n";
        echo "  php playlist-to-rss.php --batch <input_dir> <output_dir>\n";
        echo "  php playlist-to-rss.php --help\n\n";
        echo "Options:\n";
        echo "  --batch     Process all playlists in a directory\n";
        echo "  --help      Show this help message\n\n";
        echo "Supported formats: M3U, M3U8, PLS, TXT\n";
        return 1;
    }
    
    if ($argv[1] === '--help') {
        return main(1, []);
    }
    
    try {
        $converter = new PlaylistToRSSConverter([
            'title' => 'Radio Bahrain RSS Feed',
            'description' => 'Automated RSS feed generated from playlist',
            'link' => 'https://radiobahrain.fm',
            'image_url' => 'https://radiobahrain.fm/logo.png'
        ]);
        
        if ($argv[1] === '--batch') {
            if ($argc < 4) {
                echo "Error: Batch mode requires input and output directories\n";
                return 1;
            }
            
            $results = $converter->batchConvert($argv[2], $argv[3]);
            
            echo "Batch conversion completed:\n";
            foreach ($results as $file => $result) {
                echo "  {$file}: {$result['status']}";
                if ($result['status'] === 'success') {
                    echo " -> {$result['output']}";
                } else {
                    echo " ({$result['message']})";
                }
                echo "\n";
            }
        } else {
            $inputFile = $argv[1];
            $outputFile = $argv[2] ?? pathinfo($inputFile, PATHINFO_FILENAME) . '.rss';
            
            $rssContent = $converter->convertPlaylistToRSS($inputFile, $outputFile);
            
            echo "Successfully converted {$inputFile} to {$outputFile}\n";
            
            if ($converter->validateRSS($rssContent)) {
                echo "RSS feed validation: PASSED\n";
            } else {
                echo "RSS feed validation: FAILED\n";
            }
        }
        
        return 0;
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return 1;
    }
}

// Run CLI interface if called directly
if (php_sapi_name() === 'cli' && isset($argv) && basename($argv[0]) === 'playlist-to-rss.php') {
    exit(main($argc, $argv));
}

?>