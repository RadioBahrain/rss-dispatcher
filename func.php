<?php

/**
 * RSS Dispatcher Core Functions
 * 
 * Core functionality for RSS generation and playlist management
 * 
 * @author Radio Bahrain Development Team
 * @version 1.0.0
 * @license MIT
 */

// Load dependencies if available
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}

/**
 * Enhanced RSS list function with error handling
 * 
 * @param string $url RSS endpoint URL
 * @return array|false RSS data or false on failure
 */
function call_rss_list($url = 'http://podcast.adhari.com/list/rss')
{
    try {
        // Use cURL as fallback if Guzzle is not available
        if (class_exists('Guzzle\Http\Client')) {
            $client = new Guzzle\Http\Client();
            $response = $client->get($url)->send();
            return $response->getBody(true);
        } else {
            return fetch_with_curl($url);
        }
    } catch (Exception $e) {
        error_log("RSS fetch error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch RSS content using cURL
 * 
 * @param string $url URL to fetch
 * @return string|false Response content or false on failure
 */
function fetch_with_curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RSS Dispatcher v1.0.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        return false;
    }
    
    return $response;
}

/**
 * Generate RSS feed from array of audio tracks
 * 
 * @param array $tracks Array of track data
 * @param array $options RSS generation options
 * @return string RSS XML content
 */
function generate_rss_feed($tracks, $options = [])
{
    $defaultOptions = [
        'title' => 'Radio Bahrain Playlist',
        'description' => 'Generated RSS feed from playlist',
        'link' => 'https://radiobahrain.fm',
        'language' => 'ar-BH'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    // Create RSS structure
    $rss = $xml->createElement('rss');
    $rss->setAttribute('version', '2.0');
    $xml->appendChild($rss);
    
    $channel = $xml->createElement('channel');
    $rss->appendChild($channel);
    
    // Add channel metadata
    $channel->appendChild($xml->createElement('title', htmlspecialchars($options['title'])));
    $channel->appendChild($xml->createElement('description', htmlspecialchars($options['description'])));
    $channel->appendChild($xml->createElement('link', htmlspecialchars($options['link'])));
    $channel->appendChild($xml->createElement('language', $options['language']));
    $channel->appendChild($xml->createElement('lastBuildDate', date('r')));
    
    // Add tracks as items
    foreach ($tracks as $track) {
        $item = $xml->createElement('item');
        
        $item->appendChild($xml->createElement('title', htmlspecialchars($track['title'] ?? 'Unknown Track')));
        $item->appendChild($xml->createElement('description', htmlspecialchars($track['description'] ?? '')));
        $item->appendChild($xml->createElement('link', htmlspecialchars($track['url'] ?? '')));
        $item->appendChild($xml->createElement('pubDate', $track['pubDate'] ?? date('r')));
        $item->appendChild($xml->createElement('guid', htmlspecialchars($track['guid'] ?? uniqid())));
        
        // Add enclosure for audio
        if (isset($track['url'])) {
            $enclosure = $xml->createElement('enclosure');
            $enclosure->setAttribute('url', htmlspecialchars($track['url']));
            $enclosure->setAttribute('type', 'audio/mpeg');
            $enclosure->setAttribute('length', $track['length'] ?? '0');
            $item->appendChild($enclosure);
        }
        
        $channel->appendChild($item);
    }
    
    return $xml->saveXML();
}

/**
 * Save RSS content to file
 * 
 * @param string $rssContent RSS XML content
 * @param string $filename Output filename
 * @return bool Success status
 */
function save_rss_to_file($rssContent, $filename)
{
    $directory = dirname($filename);
    
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            return false;
        }
    }
    
    return file_put_contents($filename, $rssContent) !== false;
}

/**
 * Get list of available playlist files
 * 
 * @param string $directory Directory to scan
 * @return array List of playlist files
 */
function get_playlist_files($directory = '.')
{
    $supportedExtensions = ['m3u', 'm3u8', 'pls', 'txt'];
    $files = [];
    
    foreach ($supportedExtensions as $ext) {
        $pattern = $directory . '/*.' . $ext;
        $files = array_merge($files, glob($pattern));
    }
    
    return $files;
}

/**
 * Parse simple M3U playlist content
 * 
 * @param string $content M3U file content
 * @return array Parsed tracks
 */
function parse_m3u_content($content)
{
    $lines = explode("\n", $content);
    $tracks = [];
    $currentTrack = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || $line[0] === '#') {
            if (strpos($line, '#EXTINF:') === 0) {
                preg_match('/#EXTINF:(-?\d+),(.*)/', $line, $matches);
                if ($matches) {
                    $currentTrack['duration'] = intval($matches[1]);
                    $currentTrack['title'] = trim($matches[2]);
                }
            }
            continue;
        }
        
        // URL line
        $currentTrack['url'] = $line;
        $currentTrack['title'] = $currentTrack['title'] ?? basename($line);
        $currentTrack['description'] = "Audio track: " . $currentTrack['title'];
        $currentTrack['pubDate'] = date('r');
        $currentTrack['guid'] = md5($line . time());
        
        $tracks[] = $currentTrack;
        $currentTrack = [];
    }
    
    return $tracks;
}

/**
 * Validate RSS XML content
 * 
 * @param string $rssContent RSS XML content
 * @return bool Validation result
 */
function validate_rss_content($rssContent)
{
    try {
        $xml = new DOMDocument();
        $xml->loadXML($rssContent);
        
        // Check for required elements
        $rssElements = $xml->getElementsByTagName('rss');
        if ($rssElements->length !== 1) {
            return false;
        }
        
        $channelElements = $xml->getElementsByTagName('channel');
        if ($channelElements->length !== 1) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Create a simple HTML interface for RSS management
 * 
 * @return string HTML content
 */
function render_rss_interface()
{
    $playlistFiles = get_playlist_files();
    
    $html = '<div class="rss-interface">';
    $html .= '<h2>RSS Feed Generator</h2>';
    
    if (empty($playlistFiles)) {
        $html .= '<p>No playlist files found. Upload M3U, PLS, or TXT files to get started.</p>';
    } else {
        $html .= '<h3>Available Playlists:</h3>';
        $html .= '<ul>';
        foreach ($playlistFiles as $file) {
            $basename = basename($file);
            $rssFile = pathinfo($file, PATHINFO_FILENAME) . '.rss';
            $html .= "<li>{$basename} ";
            $html .= "<a href=\"?convert={$file}&output={$rssFile}\" class=\"btn btn-convert\">Convert to RSS</a>";
            if (file_exists($rssFile)) {
                $html .= " <a href=\"{$rssFile}\" class=\"btn btn-download\">Download RSS</a>";
            }
            $html .= "</li>";
        }
        $html .= '</ul>';
    }
    
    $html .= '</div>';
    
    return $html;
}
